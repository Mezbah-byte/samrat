-- ---------------------------------------------------------------------
-- Agent float system (agent panel v2).
--
-- Turns the agent from a reviewer into a money mover. An agent now holds
-- three balances of their own:
--
--   deposit_balance    float bought from the admin, spent settling user
--                      deposits the agent accepts
--   withdraw_balance   collected when the agent pays a user's withdrawal
--                      out of their own pocket; cashed out from the admin
--   commission_balance earnings; cashed out, or transferred into the float
--
-- The old recommendation flow and the old team-based commission accrual are
-- untouched and keep running. This file only adds the float machinery beside
-- them, gated behind settings that all default to the current behaviour.
--
--   mysql -u <user> -p <database> < database/upgrade_agent_float.sql
--
-- Re-runnable. Every change checks information_schema first, so a database
-- already built from database/schema.sql just skips them silently.
--
-- THIS is the file to run on an existing/live database. Never run
-- database/schema.sql there: it opens with DROP DATABASE and would destroy
-- every user, deposit and transaction. schema.sql is for fresh installs only.
--
-- Additive only: no existing row is updated and no column is dropped, so the
-- currently deployed code keeps working against the new schema. Take a backup
-- first anyway - the ALTERs below rewrite the agents, deposits and
-- withdrawals tables and cannot be undone from inside MySQL.
--
-- Requires database/upgrade_agent_panel.sql to have been run first.
-- ---------------------------------------------------------------------

-- ---------------------------------------------------------------------
-- Agent receive wallets
--
-- The addresses a user is shown after picking an agent. Deliberately not
-- folded into `deposit_methods`: those are the platform's own wallets, owned
-- by the admin, and nothing an agent edits may ever appear in that list.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agent_wallets` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agent_id`       INT UNSIGNED NOT NULL,
  `label`          VARCHAR(80)  NOT NULL COMMENT 'shown to the user, e.g. "Binance Pay"',
  `network`        VARCHAR(30)  NOT NULL,
  `currency`       VARCHAR(20)  NOT NULL DEFAULT 'USDT',
  `wallet_address` VARCHAR(191) NOT NULL,
  `qr_image`       VARCHAR(255) DEFAULT NULL,
  `instructions`   TEXT DEFAULT NULL,
  `min_amount`     DECIMAL(18,8) NOT NULL DEFAULT 0,
  `max_amount`     DECIMAL(18,8) NOT NULL DEFAULT 0 COMMENT '0 = no ceiling',
  `sort_order`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_agentwallet_agent` (`agent_id`,`status`,`sort_order`),
  CONSTRAINT `fk_agentwallet_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Agent ledger
--
-- What `transactions` is for users, this is for agents: the one place every
-- movement of an agent balance is recorded. Agent_wallet_lib writes a row
-- here inside the same transaction as the balance UPDATE, so SUM(amount) per
-- (agent_id, wallet) always equals the stored column.
--
-- `amount` is signed: positive credits, negative debits.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agent_ledger` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agent_id`        INT UNSIGNED NOT NULL,
  `wallet`          ENUM('deposit','withdraw','commission') NOT NULL,
  `type`            ENUM('float_purchase','deposit_settle','deposit_refund','withdraw_settle',
                         'withdraw_reverse','commission','payout','payout_refund',
                         'transfer_in','transfer_out','admin_credit','admin_debit') NOT NULL,
  `amount`          DECIMAL(18,8) NOT NULL COMMENT 'signed: + credit, - debit',
  `balance_after`   DECIMAL(18,8) NOT NULL,
  `reference_table` VARCHAR(40) DEFAULT NULL,
  `reference_id`    INT UNSIGNED DEFAULT NULL,
  `description`     VARCHAR(255) DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_aledger_agent` (`agent_id`,`wallet`,`created_at`),
  KEY `ix_aledger_type` (`type`,`created_at`),
  KEY `ix_aledger_ref` (`reference_table`,`reference_id`),
  CONSTRAINT `fk_aledger_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Agent float orders (agent buys balance from the admin)
--
-- Mirrors `deposits`: the agent sends USDT to a platform wallet off-platform
-- and submits the hash; an admin verifies it and credits the float. Float is
-- sold at par - the agent's earnings come from the commission percentages,
-- never from a purchase discount, so the ledger never has to reconcile two
-- different values for the same dollar.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agent_float_orders` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agent_id`          INT UNSIGNED NOT NULL,
  `deposit_method_id` INT UNSIGNED DEFAULT NULL,
  `amount`            DECIMAL(18,8) NOT NULL,
  `network`           VARCHAR(30) DEFAULT NULL,
  `txid`              VARCHAR(191) NOT NULL,
  `proof_image`       VARCHAR(255) DEFAULT NULL,
  `status`            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note`        VARCHAR(500) DEFAULT NULL,
  `reviewed_by`       INT UNSIGNED DEFAULT NULL,
  `reviewed_at`       DATETIME DEFAULT NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_floatorder_txid` (`txid`),
  KEY `ix_floatorder_agent` (`agent_id`,`status`),
  KEY `ix_floatorder_status` (`status`,`created_at`),
  CONSTRAINT `fk_floatorder_agent`  FOREIGN KEY (`agent_id`)  REFERENCES `agents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_floatorder_method` FOREIGN KEY (`deposit_method_id`) REFERENCES `deposit_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_floatorder_admin`  FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Agent payouts (agent cashes out from the admin)
--
-- `source` names which of the agent's wallets is being drained. The amount is
-- held - debited from that wallet - the moment the request is made, exactly
-- as a user withdrawal holds the user's balance, so the same balance cannot
-- be requested twice while an admin reviews it.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agent_payouts` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agent_id`       INT UNSIGNED NOT NULL,
  `source`         ENUM('withdraw','commission') NOT NULL,
  `amount`         DECIMAL(18,8) NOT NULL,
  `fee_percent`    DECIMAL(8,4)  NOT NULL DEFAULT 0,
  `fee`            DECIMAL(18,8) NOT NULL DEFAULT 0,
  `net_amount`     DECIMAL(18,8) NOT NULL,
  `network`        VARCHAR(30)  NOT NULL,
  `wallet_address` VARCHAR(191) NOT NULL,
  `status`         ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  `txid`           VARCHAR(191) DEFAULT NULL,
  `admin_note`     VARCHAR(500) DEFAULT NULL,
  `processed_by`   INT UNSIGNED DEFAULT NULL,
  `processed_at`   DATETIME DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_apayout_agent` (`agent_id`,`status`),
  KEY `ix_apayout_status` (`status`,`created_at`),
  CONSTRAINT `fk_apayout_agent` FOREIGN KEY (`agent_id`)     REFERENCES `agents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_apayout_admin` FOREIGN KEY (`processed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Alters on existing tables. Each is a no-op when already applied.
-- ---------------------------------------------------------------------

-- The three agent balances, plus the per-agent withdraw-commission override
-- (commission_deposit_percent already exists and is reused) and the two
-- switches an agent flips when they stop taking work.
SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `agents`
     ADD COLUMN `deposit_balance`    DECIMAL(18,8) NOT NULL DEFAULT 0 AFTER `total_commission`,
     ADD COLUMN `withdraw_balance`   DECIMAL(18,8) NOT NULL DEFAULT 0 AFTER `deposit_balance`,
     ADD COLUMN `commission_balance` DECIMAL(18,8) NOT NULL DEFAULT 0 AFTER `withdraw_balance`,
     ADD COLUMN `accepting_deposits`    TINYINT(1) NOT NULL DEFAULT 1 AFTER `commission_balance`,
     ADD COLUMN `accepting_withdrawals` TINYINT(1) NOT NULL DEFAULT 1 AFTER `accepting_deposits`,
     ADD COLUMN `commission_settle_percent`   DECIMAL(8,4) DEFAULT NULL COMMENT ''NULL = use the agent_deposit_commission_percent setting'' AFTER `commission_profit_percent`,
     ADD COLUMN `commission_withdraw_percent` DECIMAL(8,4) DEFAULT NULL COMMENT ''NULL = use the agent_withdraw_commission_percent setting'' AFTER `commission_settle_percent`',
  'SELECT 1')
  FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'agents' AND `COLUMN_NAME` = 'deposit_balance');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Separate guard for the float-system deposit override, so a database that
-- took the block above before this column existed still picks it up.
-- `commission_deposit_percent` is left alone: it keeps driving the old
-- team-based accrual, which runs unchanged alongside the float system.
SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `agents`
     ADD COLUMN `commission_settle_percent` DECIMAL(8,4) DEFAULT NULL COMMENT ''NULL = use the agent_deposit_commission_percent setting''',
  'SELECT 1')
  FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'agents' AND `COLUMN_NAME` = 'commission_settle_percent');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- An agent-routed deposit carries its own lifecycle beside the admin one.
-- 'none' is the default so every existing row, and every deposit made through
-- the unchanged admin path, reads as "no agent involved".
SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `deposits`
     ADD COLUMN `agent_wallet_id`   INT UNSIGNED DEFAULT NULL AFTER `agent_reviewed_at`,
     ADD COLUMN `agent_status`      ENUM(''none'',''pending'',''accepted'',''rejected'',''expired'') NOT NULL DEFAULT ''none'' AFTER `agent_wallet_id`,
     ADD COLUMN `agent_accepted_at` DATETIME DEFAULT NULL AFTER `agent_status`,
     ADD COLUMN `agent_commission`  DECIMAL(18,8) NOT NULL DEFAULT 0 AFTER `agent_accepted_at`,
     ADD KEY `ix_deposits_agent_status` (`agent_id`,`agent_status`),
     ADD CONSTRAINT `fk_deposits_agentwallet` FOREIGN KEY (`agent_wallet_id`) REFERENCES `agent_wallets` (`id`) ON DELETE SET NULL',
  'SELECT 1')
  FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'deposits' AND `COLUMN_NAME` = 'agent_status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Same shape on the withdrawal side. `agent_txid` is the hash of the payment
-- the agent made to the user out of their own pocket - distinct from `txid`,
-- which stays the admin's own payout hash on the admin-routed path.
SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `withdrawals`
     ADD COLUMN `agent_status`     ENUM(''none'',''pending'',''accepted'',''rejected'',''expired'') NOT NULL DEFAULT ''none'' AFTER `agent_reviewed_at`,
     ADD COLUMN `agent_paid_at`    DATETIME DEFAULT NULL AFTER `agent_status`,
     ADD COLUMN `agent_txid`       VARCHAR(191) DEFAULT NULL AFTER `agent_paid_at`,
     ADD COLUMN `agent_commission` DECIMAL(18,8) NOT NULL DEFAULT 0 AFTER `agent_txid`,
     ADD KEY `ix_wd_agent_status` (`agent_id`,`agent_status`)',
  'SELECT 1')
  FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'withdrawals' AND `COLUMN_NAME` = 'agent_status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Two new commission sources. The existing 'deposit' and 'daily_profit' rows
-- keep their meaning: that accrual still runs, still pays the linked user's
-- main balance, and is unaffected by anything in this file.
SET @sql := COALESCE((SELECT IF(LOCATE('agent_deposit', `COLUMN_TYPE`) = 0,
  'ALTER TABLE `agent_commissions` MODIFY COLUMN `source` ENUM(''deposit'',''daily_profit'',''agent_deposit'',''agent_withdraw'') NOT NULL',
  'SELECT 1')
  FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'agent_commissions' AND `COLUMN_NAME` = 'source'), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Where the commission landed. 'balance' is the old behaviour (the linked
-- user's main wallet) and stays the default, so existing rows read true.
SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `agent_commissions`
     ADD COLUMN `wallet` ENUM(''balance'',''commission'') NOT NULL DEFAULT ''balance'' AFTER `settled`',
  'SELECT 1')
  FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'agent_commissions' AND `COLUMN_NAME` = 'wallet');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- Settings.
--
-- deposit_route / withdraw_route default to 'admin' - the behaviour running
-- in production today. Nothing about the user experience changes until an
-- admin moves them to 'both' or 'agent', and moving them back is the whole
-- rollback plan.
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `settings` (`key`,`value`,`group`,`type`,`label`,`sort_order`) VALUES
('agent_float_enabled','0','agent','boolean','Agent Float System Enabled',10),
('deposit_route','admin','agent','text','Deposit Route (admin / agent / both)',11),
('withdraw_route','admin','agent','text','Withdrawal Route (admin / agent / both)',12),
('agent_deposit_commission_percent','1','agent','number','Agent Commission per Deposit Settled (%)',13),
('agent_withdraw_commission_percent','1','agent','number','Agent Commission per Withdrawal Paid (%)',14),
('agent_accept_timeout_hours','6','agent','number','Hours Before an Unanswered Request Escalates to Admin',15),
('agent_payout_fee_percent','0','agent','number','Fee on Agent Cash-Out (%)',16),
('agent_min_float','0','agent','number','Minimum Float an Agent Must Hold to Be Listed',17);
