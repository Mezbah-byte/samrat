-- ---------------------------------------------------------------------
-- Read-only checks around database/upgrade_agent_float.sql
--
-- Nothing here writes. Safe to run on the live database at any time.
--
--   mysql -u <user> -p <database> < database/verify_agent_float.sql
--
-- Run it BEFORE the migration to confirm the database is ready for it, and
-- AFTER to confirm every piece landed. The same output answers both: before,
-- the prerequisite rows read "OK" and the rest read "MISSING"; after, every
-- row reads "OK".
-- ---------------------------------------------------------------------

SELECT '--- prerequisites (must be OK before migrating) ---' AS ``;

SELECT 'agents table'            AS item,
       IF(COUNT(*) = 1, 'OK', 'MISSING - run upgrade_agent_panel.sql first') AS state
  FROM `information_schema`.`TABLES`
 WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'agents'
UNION ALL
SELECT 'agent_commissions table',
       IF(COUNT(*) = 1, 'OK', 'MISSING - run upgrade_agent_panel.sql first')
  FROM `information_schema`.`TABLES`
 WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'agent_commissions'
UNION ALL
SELECT 'deposits.agent_id',
       IF(COUNT(*) = 1, 'OK', 'MISSING - run upgrade_agent_panel.sql first')
  FROM `information_schema`.`COLUMNS`
 WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'deposits' AND `COLUMN_NAME` = 'agent_id'
UNION ALL
SELECT 'withdrawals.agent_id',
       IF(COUNT(*) = 1, 'OK', 'MISSING - run upgrade_agent_panel.sql first')
  FROM `information_schema`.`COLUMNS`
 WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'withdrawals' AND `COLUMN_NAME` = 'agent_id';

SELECT '--- new tables (OK after migrating) ---' AS ``;

SELECT `TABLE_NAME` AS item, 'OK' AS state
  FROM `information_schema`.`TABLES`
 WHERE `TABLE_SCHEMA` = DATABASE()
   AND `TABLE_NAME` IN ('agent_wallets','agent_ledger','agent_float_orders','agent_payouts')
UNION ALL
SELECT t.name, 'MISSING'
  FROM (SELECT 'agent_wallets' AS name UNION ALL SELECT 'agent_ledger'
        UNION ALL SELECT 'agent_float_orders' UNION ALL SELECT 'agent_payouts') t
 WHERE t.name NOT IN (SELECT `TABLE_NAME` FROM `information_schema`.`TABLES`
                       WHERE `TABLE_SCHEMA` = DATABASE());

SELECT '--- new columns (OK after migrating) ---' AS ``;

-- A column that did not land simply will not appear, so count the rows.
SELECT CONCAT(`TABLE_NAME`, '.', `COLUMN_NAME`) AS item, 'OK' AS state
  FROM `information_schema`.`COLUMNS`
 WHERE `TABLE_SCHEMA` = DATABASE()
   AND ((`TABLE_NAME` = 'agents' AND `COLUMN_NAME` IN
          ('deposit_balance','withdraw_balance','commission_balance',
           'accepting_deposits','accepting_withdrawals',
           'commission_settle_percent','commission_withdraw_percent'))
     OR (`TABLE_NAME` = 'deposits' AND `COLUMN_NAME` IN
          ('agent_wallet_id','agent_status','agent_accepted_at','agent_commission'))
     OR (`TABLE_NAME` = 'withdrawals' AND `COLUMN_NAME` IN
          ('agent_status','agent_paid_at','agent_txid','agent_commission'))
     OR (`TABLE_NAME` = 'agent_commissions' AND `COLUMN_NAME` = 'wallet'))
 ORDER BY `TABLE_NAME`, `COLUMN_NAME`;

SELECT 'expect 16 rows above' AS note;

SELECT '--- enum widened ---' AS ``;

SELECT 'agent_commissions.source' AS item,
       IF(LOCATE('agent_deposit', `COLUMN_TYPE`) > 0 AND LOCATE('agent_withdraw', `COLUMN_TYPE`) > 0,
          'OK', 'NOT WIDENED') AS state
  FROM `information_schema`.`COLUMNS`
 WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'agent_commissions' AND `COLUMN_NAME` = 'source';

SELECT '--- settings (8 rows, routes must read admin on day one) ---' AS ``;

SELECT `key` AS item, `value` AS state
  FROM `settings`
 WHERE `key` IN ('agent_float_enabled','deposit_route','withdraw_route',
                 'agent_deposit_commission_percent','agent_withdraw_commission_percent',
                 'agent_accept_timeout_hours','agent_payout_fee_percent','agent_min_float')
 ORDER BY `sort_order`;

SELECT '--- existing data untouched (every count must be 0) ---' AS ``;

-- Guarded the same way the migration is, so this file also runs cleanly
-- BEFORE the migration, when these columns do not exist yet.
SET @sql := (SELECT IF(COUNT(*) = 1,
  'SELECT ''deposits pulled onto the agent route'' AS item, COUNT(*) AS state
     FROM `deposits` WHERE `agent_status` <> ''none''
   UNION ALL
   SELECT ''withdrawals pulled onto the agent route'', COUNT(*)
     FROM `withdrawals` WHERE `agent_status` <> ''none''
   UNION ALL
   SELECT ''agents holding a non-zero balance'', COUNT(*)
     FROM `agents` WHERE `deposit_balance` <> 0 OR `withdraw_balance` <> 0 OR `commission_balance` <> 0',
  'SELECT ''not migrated yet'' AS item, ''skipped'' AS state')
  FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'deposits' AND `COLUMN_NAME` = 'agent_status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT '--- agent wallet ledger drift (must be empty) ---' AS ``;

SET @sql := (SELECT IF(COUNT(*) = 1,
  'SELECT a.id AS item,
          CONCAT(''deposit '', ROUND(a.deposit_balance - COALESCE(d.total, 0), 8),
                 '' / withdraw '', ROUND(a.withdraw_balance - COALESCE(w.total, 0), 8),
                 '' / commission '', ROUND(a.commission_balance - COALESCE(c.total, 0), 8)) AS state
     FROM `agents` a
     LEFT JOIN (SELECT agent_id, SUM(amount) total FROM `agent_ledger` WHERE wallet = ''deposit''    GROUP BY agent_id) d ON d.agent_id = a.id
     LEFT JOIN (SELECT agent_id, SUM(amount) total FROM `agent_ledger` WHERE wallet = ''withdraw''   GROUP BY agent_id) w ON w.agent_id = a.id
     LEFT JOIN (SELECT agent_id, SUM(amount) total FROM `agent_ledger` WHERE wallet = ''commission'' GROUP BY agent_id) c ON c.agent_id = a.id
    WHERE ABS(a.deposit_balance    - COALESCE(d.total, 0)) > 0.00000001
       OR ABS(a.withdraw_balance   - COALESCE(w.total, 0)) > 0.00000001
       OR ABS(a.commission_balance - COALESCE(c.total, 0)) > 0.00000001',
  'SELECT ''not migrated yet'' AS item, ''skipped'' AS state')
  FROM `information_schema`.`TABLES`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'agent_ledger');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
