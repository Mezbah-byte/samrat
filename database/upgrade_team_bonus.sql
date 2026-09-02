-- ---------------------------------------------------------------------
-- Team volume bonus.
--
-- A third income stream, next to daily ad profit and per-deposit referral
-- commission. Where referral_commissions pays a percentage of one deposit,
-- this pays a flat milestone bonus once the purchases made by a user's DIRECT
-- referrals add up to a target the admin sets.
--
--   Tier "Bronze": team buys 1,000 in total  -> 50 bonus
--   Tier "Gold"  : one member buys 5,000     -> 300 bonus   (mode = 'single')
--
-- Reaching a target unlocks the tier; the user then presses Claim and the
-- money is credited to their normal balance as a `team_bonus` transaction, so
-- the SUM(transactions.amount) = users.balance invariant still holds.
--
-- Volume is lifetime cumulative and never resets, and each tier is claimable
-- once per user.
--
--   mysql -u root samrat_db < database/upgrade_team_bonus.sql
--
-- Re-runnable. Every change checks information_schema first, so a database
-- already built from database/schema.sql just skips it silently.
--
-- Additive only: no existing row is updated and no column is dropped, so the
-- currently deployed code keeps working against the new schema. The one ALTER
-- that rewrites an existing table is the `transactions`.`type` enum widening
-- at the bottom - take a backup first.
-- ---------------------------------------------------------------------

-- ---------------------------------------------------------------------
-- The ladder. One row per milestone.
--
-- `mode` decides how the target is measured:
--   combined - the summed volume of every direct referral
--   single   - the largest single purchase any one of them has made
--
-- A tier is never deleted once it has paid out; it is switched to inactive,
-- exactly as referral_levels handles a generation with history.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_bonus_tiers` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(80) NOT NULL,
  `target_volume` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `bonus_amount`  DECIMAL(18,8) NOT NULL DEFAULT 0,
  `mode`          ENUM('combined','single') NOT NULL DEFAULT 'combined'
                  COMMENT 'combined = whole team summed, single = one member''s biggest purchase',
  `min_referrals` SMALLINT UNSIGNED NOT NULL DEFAULT 0
                  COMMENT 'extra gate: how many direct referrals must have bought at all. 0 = off',
  `sort_order`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_tbt_status_target` (`status`,`target_volume`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- The claim ledger: one row per user per tier, created the moment the target
-- is met and flipped to 'claimed' when the user takes the money.
--
-- target_volume / bonus_amount / mode are snapshots. An admin who later edits
-- a tier changes what is still to come, never what was already promised or
-- paid.
--
-- UNIQUE (user_id, tier_id) is the real double-pay guard, mirroring
-- uq_refcom_deposit_level on referral_commissions and uq_agentcomm on
-- agent_commissions. The PHP checks are a courtesy; this is the guarantee.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_bonus_claims` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED NOT NULL,
  `tier_id`          INT UNSIGNED NOT NULL,
  `target_volume`    DECIMAL(18,8) NOT NULL,
  `bonus_amount`     DECIMAL(18,8) NOT NULL,
  `mode`             ENUM('combined','single') NOT NULL DEFAULT 'combined',
  `volume_at_unlock` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `status`           ENUM('unlocked','claimed') NOT NULL DEFAULT 'unlocked',
  `unlocked_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `claimed_at`       DATETIME DEFAULT NULL,
  `transaction_id`   BIGINT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tbc_user_tier` (`user_id`,`tier_id`),
  KEY `ix_tbc_user_status` (`user_id`,`status`),
  KEY `ix_tbc_tier` (`tier_id`),
  CONSTRAINT `fk_tbc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)             ON DELETE CASCADE,
  CONSTRAINT `fk_tbc_tier` FOREIGN KEY (`tier_id`) REFERENCES `team_bonus_tiers` (`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Denormalised counters on the user row.
--
-- Kept here for the same reason total_referral_bonus and total_earned already
-- are: the progress bar renders on the dashboard and the sidebar badge on
-- every single page, and neither can afford a SUM() over `deposits` per load.
--
-- Team_bonus_lib::recompute() rebuilds all three from `deposits` and runs
-- nightly from the cron, so drift from a hand-edited deposit self-heals.
-- ---------------------------------------------------------------------
SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `team_volume` DECIMAL(18,8) NOT NULL DEFAULT 0 COMMENT ''lifetime approved deposits of direct referrals'' AFTER `total_referral_bonus`',
  'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'team_volume');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `team_best_single` DECIMAL(18,8) NOT NULL DEFAULT 0 COMMENT ''largest single approved deposit by any direct referral'' AFTER `team_volume`',
  'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'team_best_single');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `users` ADD COLUMN `team_buyers` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''direct referrals with at least one approved deposit'' AFTER `team_best_single`',
  'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'team_buyers');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- The new money type. Appended to the enum - the existing values keep their
-- ordinals, so no stored row changes meaning.
-- ---------------------------------------------------------------------
SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `transactions` MODIFY COLUMN `type` ENUM(''deposit'',''investment'',''daily_profit'',''referral_bonus'',''withdrawal'',''withdrawal_fee'',''refund'',''admin_credit'',''admin_debit'',''agent_commission'',''team_bonus'') NOT NULL',
  'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions' AND COLUMN_NAME = 'type'
    AND COLUMN_TYPE LIKE '%team_bonus%');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- Backfill the counters from the deposits already on record, so a live
-- database starts with correct progress instead of everyone at zero.
--
-- Direct referrals only: one hop up `users`.`referred_by`.
--
-- Staged through a temporary table rather than a derived table joined onto
-- `users`: the aggregate has to read `users` to find each depositor's
-- referrer, and MySQL refuses (error 1093) to read the table an UPDATE is
-- writing. Running this again simply recomputes the same figures, which is
-- exactly what Team_bonus_lib::recompute() does from PHP.
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS `tmp_team_totals`;

CREATE TEMPORARY TABLE `tmp_team_totals` (
  `referrer_id` INT UNSIGNED NOT NULL,
  `volume`      DECIMAL(18,8) NOT NULL DEFAULT 0,
  `best_single` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `buyers`      INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`referrer_id`)
) ENGINE=InnoDB;

INSERT INTO `tmp_team_totals` (`referrer_id`,`volume`,`best_single`,`buyers`)
SELECT r.`referred_by`,
       SUM(d.`amount`),
       MAX(d.`amount`),
       COUNT(DISTINCT d.`user_id`)
  FROM `deposits` d
  JOIN `users` r ON r.`id` = d.`user_id`
 WHERE d.`status` = 'approved' AND r.`referred_by` IS NOT NULL
 GROUP BY r.`referred_by`;

UPDATE `users` u
LEFT JOIN `tmp_team_totals` t ON t.`referrer_id` = u.`id`
   SET u.`team_volume`      = COALESCE(t.`volume`, 0),
       u.`team_best_single` = COALESCE(t.`best_single`, 0),
       u.`team_buyers`      = COALESCE(t.`buyers`, 0);

DROP TEMPORARY TABLE `tmp_team_totals`;

-- ---------------------------------------------------------------------
-- Feature switches. Both live on the Finance tab beside the referral rules.
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `settings` (`key`,`value`,`group`,`type`,`label`,`sort_order`) VALUES
('team_bonus_enabled','1','finance','boolean','Team Volume Bonus Enabled',7),
('team_bonus_require_active_upline','1','finance','boolean','Only active accounts can claim a team bonus',8);

-- ---------------------------------------------------------------------
-- Three starter tiers so the admin screen is not empty on first open. They
-- are seeded INACTIVE on purpose: nothing pays out until an admin has looked
-- at the numbers and switched them on.
--
-- INSERT IGNORE cannot help here (there is no natural unique key), so the
-- guard is an explicit emptiness check: seed only into an empty ladder, never
-- on top of one an admin has already edited.
-- ---------------------------------------------------------------------
SET @sql := IF((SELECT COUNT(*) FROM `team_bonus_tiers`) = 0,
  'INSERT INTO `team_bonus_tiers` (`name`,`target_volume`,`bonus_amount`,`mode`,`min_referrals`,`sort_order`,`status`) VALUES
     (''Bronze Team'',  1000,  50, ''combined'', 0, 1, ''inactive''),
     (''Silver Team'',  5000, 300, ''combined'', 0, 2, ''inactive''),
     (''Gold Team'',   10000, 750, ''combined'', 0, 3, ''inactive'')',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
