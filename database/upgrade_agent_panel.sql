-- ---------------------------------------------------------------------
-- Agent panel.
--
-- Adds a third actor tier between users and admins. An agent is a trusted
-- operator promoted out of the user base: own table, own session key, own
-- login at /agent/login, own panel scoped to their referral downline.
--
-- Agents never move money. They recommend; an admin still approves. The
-- recommendation rides on the deposit / withdrawal row itself.
--
--   mysql -u <user> -p <database> < database/upgrade_agent_panel.sql
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
-- first anyway - the two ALTERs below rewrite the deposits and withdrawals
-- tables and cannot be undone from inside MySQL.
-- ---------------------------------------------------------------------

-- ---------------------------------------------------------------------
-- Agents (deliberately separate from users and admins)
--
-- user_id is nullable so an admin can create a standalone agent, and UNIQUE
-- so a user can never hold two agent accounts. MySQL allows repeated NULLs in
-- a unique index, which is exactly the behaviour wanted here.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agents` (
  `id`                         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`                    INT UNSIGNED DEFAULT NULL COMMENT 'the user this agent was promoted from',
  `name`                       VARCHAR(120) NOT NULL,
  `username`                   VARCHAR(60)  NOT NULL,
  `email`                      VARCHAR(150) NOT NULL,
  `country`                    VARCHAR(80)  DEFAULT NULL,
  `nid_number`                 VARCHAR(40)  DEFAULT NULL,
  `nid_front`                  VARCHAR(255) DEFAULT NULL,
  `nid_back`                   VARCHAR(255) DEFAULT NULL,
  `password`                   VARCHAR(255) NOT NULL,
  `commission_deposit_percent` DECIMAL(8,4) DEFAULT NULL COMMENT 'NULL = use the agent_deposit_percent setting',
  `commission_profit_percent`  DECIMAL(8,4) DEFAULT NULL COMMENT 'NULL = use the agent_profit_percent setting',
  `total_commission`           DECIMAL(18,8) NOT NULL DEFAULT 0,
  `status`                     ENUM('active','blocked') NOT NULL DEFAULT 'active',
  `created_by`                 INT UNSIGNED DEFAULT NULL,
  `last_login_at`              DATETIME DEFAULT NULL,
  `last_login_ip`              VARCHAR(45) DEFAULT NULL,
  `created_at`                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_agents_username` (`username`),
  UNIQUE KEY `uq_agents_email` (`email`),
  UNIQUE KEY `uq_agents_user` (`user_id`),
  KEY `ix_agents_status` (`status`),
  CONSTRAINT `fk_agents_user`  FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)  ON DELETE SET NULL,
  CONSTRAINT `fk_agents_admin` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Agentship applications
--
-- "One open application per user" cannot be a partial unique index in MySQL,
-- so Agent_application_model::open_for_user() enforces it before the insert.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agent_applications` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`           INT UNSIGNED NOT NULL,
  `full_name`         VARCHAR(120) NOT NULL,
  `username`          VARCHAR(60)  NOT NULL,
  `email`             VARCHAR(150) NOT NULL,
  `country`           VARCHAR(80)  DEFAULT NULL,
  `nid_number`        VARCHAR(40)  NOT NULL,
  `nid_front`         VARCHAR(255) DEFAULT NULL,
  `nid_back`          VARCHAR(255) DEFAULT NULL,
  `team_active_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'snapshot at submit time',
  `status`            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note`        VARCHAR(500) DEFAULT NULL,
  `reviewed_by`       INT UNSIGNED DEFAULT NULL,
  `reviewed_at`       DATETIME DEFAULT NULL,
  `agent_id`          INT UNSIGNED DEFAULT NULL COMMENT 'filled in on approval',
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_agentapp_status` (`status`,`created_at`),
  KEY `ix_agentapp_user` (`user_id`,`status`),
  CONSTRAINT `fk_agentapp_user`  FOREIGN KEY (`user_id`)     REFERENCES `users` (`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_agentapp_admin` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_agentapp_agent` FOREIGN KEY (`agent_id`)    REFERENCES `agents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Agent commission ledger
--
-- The unique key is the real double-pay guard, mirroring the one
-- referral_commissions uses on (deposit_id, level).
--
-- user_id is ON DELETE SET NULL rather than CASCADE on purpose: deleting a
-- team member must not erase the agent's earnings history.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agent_commissions` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agent_id`     INT UNSIGNED NOT NULL,
  `user_id`      INT UNSIGNED DEFAULT NULL COMMENT 'the team member whose activity earned this',
  `source`       ENUM('deposit','daily_profit') NOT NULL,
  `reference_id` INT UNSIGNED NOT NULL COMMENT 'deposits.id or daily_earnings.id',
  `base_amount`  DECIMAL(18,8) NOT NULL,
  `percent`      DECIMAL(8,4)  NOT NULL,
  `amount`       DECIMAL(18,8) NOT NULL,
  `settled`      TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = credited to a linked wallet',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_agentcomm` (`agent_id`,`source`,`reference_id`),
  KEY `ix_agentcomm_agent` (`agent_id`,`created_at`),
  KEY `ix_agentcomm_user` (`user_id`),
  CONSTRAINT `fk_agentcomm_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agentcomm_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Agent activity log (mirrors admin_logs)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agent_logs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `agent_id`     INT UNSIGNED DEFAULT NULL,
  `action`       VARCHAR(120) NOT NULL,
  `module`       VARCHAR(60)  NOT NULL,
  `reference_id` INT UNSIGNED DEFAULT NULL,
  `detail`       VARCHAR(500) DEFAULT NULL,
  `ip_address`   VARCHAR(45) DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_glog_agent` (`agent_id`,`created_at`),
  CONSTRAINT `fk_glog_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Alters on existing tables. Each is a no-op when already applied.
-- ---------------------------------------------------------------------

-- Agent login reuses the existing throttle, which keys off login_attempts.scope.
-- COALESCE guards the case where the column is missing entirely: a NULL @sql
-- would make PREPARE fail rather than skip.
SET @sql := COALESCE((SELECT IF(LOCATE('agent', `COLUMN_TYPE`) = 0,
  'ALTER TABLE `login_attempts` MODIFY COLUMN `scope` ENUM(''user'',''admin'',''agent'') NOT NULL DEFAULT ''user''',
  'SELECT 1')
  FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'login_attempts' AND `COLUMN_NAME` = 'scope'), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agent commission paid to a linked user lands in the one money ledger.
SET @sql := COALESCE((SELECT IF(LOCATE('agent_commission', `COLUMN_TYPE`) = 0,
  'ALTER TABLE `transactions` MODIFY COLUMN `type` ENUM(''deposit'',''investment'',''daily_profit'',''referral_bonus'',''withdrawal'',''withdrawal_fee'',''refund'',''admin_credit'',''admin_debit'',''agent_commission'') NOT NULL',
  'SELECT 1')
  FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'transactions' AND `COLUMN_NAME` = 'type'), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Recommendation columns. The agent writes these four and nothing else; the
-- admin's own status / reviewed_by / processed_by columns are untouched.
SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `deposits`
     ADD COLUMN `agent_id` INT UNSIGNED DEFAULT NULL AFTER `reviewed_at`,
     ADD COLUMN `agent_recommendation` ENUM(''approve'',''reject'') DEFAULT NULL AFTER `agent_id`,
     ADD COLUMN `agent_note` VARCHAR(500) DEFAULT NULL AFTER `agent_recommendation`,
     ADD COLUMN `agent_reviewed_at` DATETIME DEFAULT NULL AFTER `agent_note`,
     ADD KEY `ix_deposits_agent` (`agent_id`),
     ADD CONSTRAINT `fk_deposits_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL',
  'SELECT 1')
  FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'deposits' AND `COLUMN_NAME` = 'agent_recommendation');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `withdrawals`
     ADD COLUMN `agent_id` INT UNSIGNED DEFAULT NULL AFTER `processed_at`,
     ADD COLUMN `agent_recommendation` ENUM(''approve'',''reject'') DEFAULT NULL AFTER `agent_id`,
     ADD COLUMN `agent_note` VARCHAR(500) DEFAULT NULL AFTER `agent_recommendation`,
     ADD COLUMN `agent_reviewed_at` DATETIME DEFAULT NULL AFTER `agent_note`,
     ADD KEY `ix_wd_agent` (`agent_id`),
     ADD CONSTRAINT `fk_wd_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL',
  'SELECT 1')
  FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'withdrawals' AND `COLUMN_NAME` = 'agent_recommendation');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- Settings. Nothing in the code hard-codes the threshold or the rates.
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `settings` (`key`,`value`,`group`,`type`,`label`,`sort_order`) VALUES
('agent_panel_enabled','1','agent','boolean','Agent Panel Enabled',1),
('agent_min_team_size','50','agent','number','Active Team Size Required to Apply',2),
('agent_team_depth','20','agent','number','Team Depth Limit (generations)',3),
('agent_deposit_percent','1','agent','number','Agent Commission on Team Deposits (%)',4),
('agent_profit_percent','0.5','agent','number','Agent Commission on Team Daily Profit (%)',5);
