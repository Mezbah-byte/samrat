-- ---------------------------------------------------------------------
-- Multi-generation referral commission.
--
-- Before: one hard-coded level, its rate living in settings.referral_percent.
-- After:  one row per generation in `referral_levels`, all of it editable from
--         Admin -> Referral Levels, and one commission row per generation per
--         deposit.
--
-- Safe to run on a live database: it keeps every existing commission row and
-- carries the old rate over as generation 1.
--
--   mysql -u root samrat_db < database/upgrade_referral_generations.sql
--
-- Re-runnable. Every schema change below checks information_schema first, so a
-- database already built from database/schema.sql just skips them silently.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `referral_levels` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level`      TINYINT UNSIGNED NOT NULL COMMENT '1 = direct referral',
  `percent`    DECIMAL(8,4) NOT NULL DEFAULT 0,
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reflevel_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generation 1 inherits whatever the old single rate was, so nothing changes
-- for existing referrers until an admin edits the table.
INSERT IGNORE INTO `referral_levels` (`level`,`percent`,`status`) VALUES
  (1, COALESCE((SELECT CAST(`value` AS DECIMAL(8,4)) FROM `settings` WHERE `key` = 'referral_percent'), 5), 'active'),
  (2, 2, 'active'),
  (3, 1, 'active');

-- One commission row per deposit per generation.
-- Each step is a no-op when the database already has it.

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `referral_commissions` ADD COLUMN `level` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT ''generation the referrer sits at'' AFTER `referred_id`',
  'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'referral_commissions' AND COLUMN_NAME = 'level');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `referral_commissions` ADD UNIQUE KEY `uq_refcom_deposit_level` (`deposit_id`,`level`)',
  'SELECT 1')
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'referral_commissions' AND INDEX_NAME = 'uq_refcom_deposit_level');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `referral_commissions` ADD KEY `ix_refcom_level` (`level`)',
  'SELECT 1')
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'referral_commissions' AND INDEX_NAME = 'ix_refcom_level');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Old one-row-per-deposit unique key, only present on pre-upgrade databases.
-- Dropped last: it is the only index covering `deposit_id` until the new
-- unique key above exists, and fk_refcom_deposit needs one.
SET @sql := (SELECT IF(COUNT(*) > 0,
  'ALTER TABLE `referral_commissions` DROP INDEX `uq_refcom_deposit`',
  'SELECT 1')
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'referral_commissions' AND INDEX_NAME = 'uq_refcom_deposit');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- The rate now lives in referral_levels, so drop the setting that used to hold
-- it and add the two rules that decide who in the upline qualifies.
DELETE FROM `settings` WHERE `key` = 'referral_percent';

INSERT IGNORE INTO `settings` (`key`,`value`,`group`,`type`,`label`,`sort_order`) VALUES
('referral_require_active_upline','1','finance','boolean','Pay only active upline accounts',5),
('referral_require_upline_investment','0','finance','boolean','Upline must hold an active plan to earn',6);
