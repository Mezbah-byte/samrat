-- =====================================================================
-- Samrat Investment Platform - schema + seed
-- MySQL / MariaDB, InnoDB, utf8mb4
-- Import:  mysql -u root < database/schema.sql
-- =====================================================================

DROP DATABASE IF EXISTS `samrat_db`;
CREATE DATABASE `samrat_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `samrat_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Sessions (CI3 database session driver)
-- ---------------------------------------------------------------------
CREATE TABLE `ci_sessions` (
  `id`         VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45)  NOT NULL,
  `timestamp`  INT UNSIGNED NOT NULL DEFAULT 0,
  `data`       BLOB         NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Admins  (deliberately separate from users)
-- ---------------------------------------------------------------------
CREATE TABLE `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100) NOT NULL,
  `username`      VARCHAR(60)  NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password`      VARCHAR(255) NOT NULL,
  `role`          ENUM('super_admin','admin','moderator') NOT NULL DEFAULT 'admin',
  `avatar`        VARCHAR(255) DEFAULT NULL,
  `status`        ENUM('active','blocked') NOT NULL DEFAULT 'active',
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`),
  UNIQUE KEY `uq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Users
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`            VARCHAR(120) NOT NULL,
  `username`             VARCHAR(60)  NOT NULL,
  `email`                VARCHAR(150) NOT NULL,
  `mobile`               VARCHAR(30)  DEFAULT NULL,
  `country`              VARCHAR(80)  DEFAULT NULL,
  `password`             VARCHAR(255) NOT NULL,
  `avatar`               VARCHAR(255) DEFAULT NULL,
  `referral_code`        VARCHAR(16)  NOT NULL,
  `referred_by`          INT UNSIGNED DEFAULT NULL,
  `balance`              DECIMAL(18,8) NOT NULL DEFAULT 0,
  `total_deposit`        DECIMAL(18,8) NOT NULL DEFAULT 0,
  `total_earned`         DECIMAL(18,8) NOT NULL DEFAULT 0,
  `total_withdrawn`      DECIMAL(18,8) NOT NULL DEFAULT 0,
  `total_referral_bonus` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `status`               ENUM('active','pending','blocked') NOT NULL DEFAULT 'active',
  `api_token`            VARCHAR(80) DEFAULT NULL,
  `api_token_at`         DATETIME DEFAULT NULL,
  `last_login_at`        DATETIME DEFAULT NULL,
  `last_login_ip`        VARCHAR(45) DEFAULT NULL,
  `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_refcode` (`referral_code`),
  UNIQUE KEY `uq_users_apitoken` (`api_token`),
  KEY `ix_users_referred_by` (`referred_by`),
  KEY `ix_users_status` (`status`),
  CONSTRAINT `fk_users_referrer` FOREIGN KEY (`referred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(150) NOT NULL,
  `token`      VARCHAR(80)  NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at`    DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pwreset_token` (`token`),
  KEY `ix_pwreset_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope`      ENUM('user','admin','agent') NOT NULL DEFAULT 'user',
  `identity`   VARCHAR(150) NOT NULL,
  `ip_address` VARCHAR(45)  NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_attempt_lookup` (`scope`,`identity`,`created_at`),
  KEY `ix_attempt_ip` (`ip_address`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Packages
-- ---------------------------------------------------------------------
CREATE TABLE `packages` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`                 VARCHAR(80) NOT NULL,
  `slug`                 VARCHAR(90) NOT NULL,
  `price`                DECIMAL(18,8) NOT NULL,
  `daily_return_percent` DECIMAL(8,4)  NOT NULL DEFAULT 2.0000,
  `duration_days`        SMALLINT UNSIGNED NOT NULL DEFAULT 100,
  `daily_ads`            SMALLINT UNSIGNED NOT NULL DEFAULT 2,
  `min_withdraw`         DECIMAL(18,8) NOT NULL DEFAULT 0,
  `image`                VARCHAR(255) DEFAULT NULL,
  `description`          TEXT DEFAULT NULL,
  `sort_order`           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status`               ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_packages_slug` (`slug`),
  KEY `ix_packages_status_sort` (`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Deposit methods (company receiving wallets)
-- ---------------------------------------------------------------------
CREATE TABLE `deposit_methods` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(80) NOT NULL,
  `network`        VARCHAR(30) NOT NULL,
  `currency`       VARCHAR(20) NOT NULL DEFAULT 'USDT',
  `wallet_address` VARCHAR(191) NOT NULL,
  `qr_image`       VARCHAR(255) DEFAULT NULL,
  `min_amount`     DECIMAL(18,8) NOT NULL DEFAULT 0,
  `instructions`   TEXT DEFAULT NULL,
  `sort_order`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_depmethod_status` (`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Deposits
-- ---------------------------------------------------------------------
CREATE TABLE `deposits` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`           INT UNSIGNED NOT NULL,
  `package_id`        INT UNSIGNED NOT NULL,
  `deposit_method_id` INT UNSIGNED DEFAULT NULL,
  `amount`            DECIMAL(18,8) NOT NULL,
  `network`           VARCHAR(30) DEFAULT NULL,
  `txid`              VARCHAR(191) NOT NULL,
  `proof_image`       VARCHAR(255) DEFAULT NULL,
  `status`            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note`        VARCHAR(500) DEFAULT NULL,
  `reviewed_by`       INT UNSIGNED DEFAULT NULL,
  `reviewed_at`       DATETIME DEFAULT NULL,
  `agent_id`             INT UNSIGNED DEFAULT NULL,
  `agent_recommendation` ENUM('approve','reject') DEFAULT NULL,
  `agent_note`           VARCHAR(500) DEFAULT NULL,
  `agent_reviewed_at`    DATETIME DEFAULT NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_deposits_txid` (`txid`),
  KEY `ix_deposits_user` (`user_id`,`status`),
  KEY `ix_deposits_status` (`status`,`created_at`),
  KEY `ix_deposits_agent` (`agent_id`),
  CONSTRAINT `fk_deposits_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_deposits_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_deposits_method`  FOREIGN KEY (`deposit_method_id`) REFERENCES `deposit_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_deposits_admin`   FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_deposits_agent`   FOREIGN KEY (`agent_id`)    REFERENCES `agents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Investments (an approved deposit becomes one active investment)
-- ---------------------------------------------------------------------
CREATE TABLE `investments` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`              INT UNSIGNED NOT NULL,
  `package_id`           INT UNSIGNED NOT NULL,
  `deposit_id`           INT UNSIGNED DEFAULT NULL,
  `amount`               DECIMAL(18,8) NOT NULL,
  `daily_return_percent` DECIMAL(8,4)  NOT NULL,
  `daily_amount`         DECIMAL(18,8) NOT NULL,
  `daily_ads`            SMALLINT UNSIGNED NOT NULL,
  `duration_days`        SMALLINT UNSIGNED NOT NULL,
  `days_credited`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `days_missed`          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `total_earned`         DECIMAL(18,8) NOT NULL DEFAULT 0,
  `start_date`           DATE NOT NULL,
  `end_date`             DATE NOT NULL,
  `status`               ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_investments_deposit` (`deposit_id`),
  KEY `ix_investments_user` (`user_id`,`status`),
  KEY `ix_investments_status` (`status`,`end_date`),
  CONSTRAINT `fk_inv_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_inv_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_inv_deposit` FOREIGN KEY (`deposit_id`) REFERENCES `deposits` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Daily earnings ledger. unique(investment_id, earn_date) = cron idempotency
-- ---------------------------------------------------------------------
CREATE TABLE `daily_earnings` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `investment_id` INT UNSIGNED NOT NULL,
  `user_id`       INT UNSIGNED NOT NULL,
  `earn_date`     DATE NOT NULL,
  `amount`        DECIMAL(18,8) NOT NULL,
  `ads_required`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `ads_watched`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status`        ENUM('pending','credited','missed') NOT NULL DEFAULT 'pending',
  `credited_at`   DATETIME DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_daily_inv_date` (`investment_id`,`earn_date`),
  KEY `ix_daily_user_date` (`user_id`,`earn_date`),
  KEY `ix_daily_status` (`status`,`earn_date`),
  CONSTRAINT `fk_daily_inv`  FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_daily_user` FOREIGN KEY (`user_id`)       REFERENCES `users` (`id`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Withdrawals
-- ---------------------------------------------------------------------
CREATE TABLE `withdrawals` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NOT NULL,
  `amount`         DECIMAL(18,8) NOT NULL,
  `fee_percent`    DECIMAL(8,4)  NOT NULL DEFAULT 5.0000,
  `fee`            DECIMAL(18,8) NOT NULL,
  `net_amount`     DECIMAL(18,8) NOT NULL,
  `network`        VARCHAR(30)  NOT NULL,
  `wallet_address` VARCHAR(191) NOT NULL,
  `status`         ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  `txid`           VARCHAR(191) DEFAULT NULL,
  `admin_note`     VARCHAR(500) DEFAULT NULL,
  `processed_by`   INT UNSIGNED DEFAULT NULL,
  `processed_at`   DATETIME DEFAULT NULL,
  `agent_id`             INT UNSIGNED DEFAULT NULL,
  `agent_recommendation` ENUM('approve','reject') DEFAULT NULL,
  `agent_note`           VARCHAR(500) DEFAULT NULL,
  `agent_reviewed_at`    DATETIME DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_wd_user` (`user_id`,`status`),
  KEY `ix_wd_status` (`status`,`created_at`),
  KEY `ix_wd_agent` (`agent_id`),
  CONSTRAINT `fk_wd_user`  FOREIGN KEY (`user_id`)      REFERENCES `users` (`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_wd_admin` FOREIGN KEY (`processed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_wd_agent` FOREIGN KEY (`agent_id`)     REFERENCES `agents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Referral commissions (level 1, one-time, on approved deposit)
-- ---------------------------------------------------------------------
CREATE TABLE `referral_levels` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level`      TINYINT UNSIGNED NOT NULL COMMENT '1 = direct referral',
  `percent`    DECIMAL(8,4) NOT NULL DEFAULT 0,
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reflevel_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `referral_commissions` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `referrer_id` INT UNSIGNED NOT NULL,
  `referred_id` INT UNSIGNED NOT NULL,
  `level`       TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'generation the referrer sits at',
  `deposit_id`  INT UNSIGNED NOT NULL,
  `percent`     DECIMAL(8,4)  NOT NULL,
  `amount`      DECIMAL(18,8) NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_refcom_deposit_level` (`deposit_id`,`level`),
  KEY `ix_refcom_referrer` (`referrer_id`,`created_at`),
  KEY `ix_refcom_level` (`level`),
  CONSTRAINT `fk_refcom_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_refcom_referred` FOREIGN KEY (`referred_id`) REFERENCES `users` (`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_refcom_deposit`  FOREIGN KEY (`deposit_id`)  REFERENCES `deposits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Single money ledger. Written only by Wallet_lib.
-- ---------------------------------------------------------------------
CREATE TABLE `transactions` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`         INT UNSIGNED NOT NULL,
  `type`            ENUM('deposit','investment','daily_profit','referral_bonus','withdrawal','withdrawal_fee','refund','admin_credit','admin_debit','agent_commission') NOT NULL,
  `amount`          DECIMAL(18,8) NOT NULL COMMENT 'signed: + credit, - debit',
  `balance_after`   DECIMAL(18,8) NOT NULL,
  `reference_table` VARCHAR(40) DEFAULT NULL,
  `reference_id`    INT UNSIGNED DEFAULT NULL,
  `description`     VARCHAR(255) DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_tx_user` (`user_id`,`created_at`),
  KEY `ix_tx_type` (`type`,`created_at`),
  KEY `ix_tx_ref` (`reference_table`,`reference_id`),
  CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Ads
-- ---------------------------------------------------------------------
CREATE TABLE `ads` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`         VARCHAR(150) NOT NULL,
  `type`          ENUM('image','video','banner','link') NOT NULL DEFAULT 'image',
  `source`        ENUM('upload','embed','vast') NOT NULL DEFAULT 'upload' COMMENT 'where the creative comes from',
  `media`         VARCHAR(255) DEFAULT NULL,
  `media_url`     VARCHAR(500) DEFAULT NULL COMMENT 'remote image or video file',
  `target_url`    VARCHAR(500) DEFAULT NULL,
  `body`          TEXT DEFAULT NULL,
  `embed_code`    TEXT DEFAULT NULL COMMENT 'ad network HTML/JS tag',
  `vast_url`      VARCHAR(500) DEFAULT NULL COMMENT 'VAST/VPAID tag URL',
  `watch_seconds` SMALLINT UNSIGNED NOT NULL DEFAULT 15,
  `placement`     ENUM('daily_task','global') NOT NULL DEFAULT 'daily_task',
  `total_views`   INT UNSIGNED NOT NULL DEFAULT 0,
  `sort_order`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `starts_at`     DATE DEFAULT NULL,
  `ends_at`       DATE DEFAULT NULL,
  `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_ads_active` (`status`,`placement`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ad_views` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `ad_id`      INT UNSIGNED NOT NULL,
  `view_date`  DATE NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_adview_user_ad_date` (`user_id`,`ad_id`,`view_date`),
  KEY `ix_adview_user_date` (`user_id`,`view_date`),
  CONSTRAINT `fk_adview_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_adview_ad`   FOREIGN KEY (`ad_id`)   REFERENCES `ads` (`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Notices
-- ---------------------------------------------------------------------
CREATE TABLE `notices` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(180) NOT NULL,
  `slug`         VARCHAR(200) NOT NULL,
  `content`      TEXT NOT NULL,
  `type`         ENUM('announcement','notice','update','promotion') NOT NULL DEFAULT 'notice',
  `image`        VARCHAR(255) DEFAULT NULL,
  `is_pinned`    TINYINT(1) NOT NULL DEFAULT 0,
  `status`       ENUM('published','draft') NOT NULL DEFAULT 'published',
  `published_at` DATETIME DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notices_slug` (`slug`),
  KEY `ix_notices_pub` (`status`,`is_pinned`,`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Settings
-- ---------------------------------------------------------------------
CREATE TABLE `settings` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`         VARCHAR(80) NOT NULL,
  `value`       TEXT DEFAULT NULL,
  `group`       VARCHAR(40) NOT NULL DEFAULT 'general',
  `type`        ENUM('text','textarea','number','boolean','image','select') NOT NULL DEFAULT 'text',
  `label`       VARCHAR(120) DEFAULT NULL,
  `options`     VARCHAR(500) DEFAULT NULL,
  `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Notifications (user_id NULL = broadcast)
-- ---------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `title`      VARCHAR(180) NOT NULL,
  `message`    TEXT NOT NULL,
  `link`       VARCHAR(255) DEFAULT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_notif_user` (`user_id`,`is_read`,`created_at`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Admin activity log
-- ---------------------------------------------------------------------
CREATE TABLE `admin_logs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`     INT UNSIGNED DEFAULT NULL,
  `action`       VARCHAR(120) NOT NULL,
  `module`       VARCHAR(60)  NOT NULL,
  `reference_id` INT UNSIGNED DEFAULT NULL,
  `detail`       VARCHAR(500) DEFAULT NULL,
  `ip_address`   VARCHAR(45) DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_alog_admin` (`admin_id`,`created_at`),
  CONSTRAINT `fk_alog_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Agents  (a third tier: separate table, separate session key, own login)
--
-- user_id is nullable so an admin can create a standalone agent, and UNIQUE
-- so a user can never hold two agent accounts. MySQL allows repeated NULLs in
-- a unique index, which is exactly the behaviour wanted here.
-- ---------------------------------------------------------------------
CREATE TABLE `agents` (
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
CREATE TABLE `agent_applications` (
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
CREATE TABLE `agent_commissions` (
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
CREATE TABLE `agent_logs` (
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

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED
-- =====================================================================

-- Default super admin. password = Admin@123  (change after first login)
INSERT INTO `admins` (`name`,`username`,`email`,`password`,`role`,`status`) VALUES
('Super Admin','admin','admin@samrat.test','$2y$10$ZByMFwNJrBXNvMnkK4/nGe/6E7kLs2rjMVf0rLKjEhjQlk6SNvcwG','super_admin','active');

-- 5 client-named packages + 4 spare slots (client asked for 9 total)
INSERT INTO `packages` (`name`,`slug`,`price`,`daily_return_percent`,`duration_days`,`daily_ads`,`min_withdraw`,`sort_order`,`status`,`description`) VALUES
('Silver',    'silver',       50.00000000, 2.0000, 100,  2,    5.00000000, 1, 'active',   'Entry level package.'),
('Gold',      'gold',        100.00000000, 2.0000, 100,  3,   10.00000000, 2, 'active',   'Popular package.'),
('Platinum',  'platinum',    300.00000000, 2.0000, 100,  4,   30.00000000, 3, 'active',   'Growth package.'),
('Diamond',   'diamond',     500.00000000, 2.0000, 100,  5,   50.00000000, 4, 'active',   'Premium package.'),
('VIP',       'vip',        1000.00000000, 2.0000, 100,  6,  100.00000000, 5, 'active',   'Top tier package.'),
('Package 6', 'package-6',  2000.00000000, 2.0000, 100,  7,  200.00000000, 6, 'inactive', 'Reserved slot - edit from admin.'),
('Package 7', 'package-7',  3000.00000000, 2.0000, 100,  8,  300.00000000, 7, 'inactive', 'Reserved slot - edit from admin.'),
('Package 8', 'package-8',  5000.00000000, 2.0000, 100,  9,  500.00000000, 8, 'inactive', 'Reserved slot - edit from admin.'),
('Package 9', 'package-9', 10000.00000000, 2.0000, 100, 10, 1000.00000000, 9, 'inactive', 'Reserved slot - edit from admin.');

-- Three generations out of the box. Add, edit or switch these off under
-- Admin -> Referral Levels; nothing else in the app hard-codes a rate.
INSERT INTO `referral_levels` (`level`,`percent`,`status`) VALUES
(1,5,'active'),
(2,2,'active'),
(3,1,'active');

INSERT INTO `deposit_methods` (`name`,`network`,`currency`,`wallet_address`,`min_amount`,`instructions`,`sort_order`,`status`) VALUES
('USDT TRC20','TRC20','USDT','TREPLACE_WITH_YOUR_TRON_ADDRESS',50,'Send only USDT over the TRON (TRC20) network. Sending any other token or using another network will lose the funds.',1,'active'),
('USDT BEP20','BEP20','USDT','0xREPLACE_WITH_YOUR_BSC_ADDRESS',50,'Send only USDT over the BNB Smart Chain (BEP20) network.',2,'active');

-- Four playable video ads so the watch flow works the moment the app is
-- installed. These are Google's public IMA sample VAST tags: real creatives,
-- zero revenue. Replace each tag with your own network's under Admin -> Ads.
INSERT INTO `ads` (`title`,`type`,`source`,`vast_url`,`body`,`watch_seconds`,`placement`,`sort_order`,`status`) VALUES
('Video ad 1','video','vast','https://pubads.g.doubleclick.net/gampad/ads?iu=/21775744923/external/single_ad_samples&sz=640x480&cust_params=sample_ct%3Dlinear&ciu_szs=300x250%2C728x90&gdfp_req=1&output=vast&unviewed_position_start=1&env=vp&impl=s&correlator=','Watch the video through to the end to bank this one.',15,'daily_task',1,'active'),
('Video ad 2','video','vast','https://pubads.g.doubleclick.net/gampad/ads?iu=/21775744923/external/single_ad_samples&sz=640x480&cust_params=sample_ct%3Dskippablelinear&ciu_szs=300x250%2C728x90&gdfp_req=1&output=vast&unviewed_position_start=1&env=vp&impl=s&correlator=','Watch the video through to the end to bank this one.',15,'daily_task',2,'active'),
('Video ad 3','video','vast','https://pubads.g.doubleclick.net/gampad/ads?iu=/21775744923/external/single_ad_samples&sz=640x480&cust_params=sample_ct%3Dredirectlinear&ciu_szs=300x250%2C728x90&gdfp_req=1&output=vast&unviewed_position_start=1&env=vp&impl=s&correlator=','Watch the video through to the end to bank this one.',15,'daily_task',3,'active'),
('Video ad 4','video','vast','https://pubads.g.doubleclick.net/gampad/ads?iu=/21775744923/external/single_ad_samples&sz=640x480&cust_params=sample_ct%3Dlinear&ciu_szs=300x250%2C728x90&gdfp_req=1&output=vast&unviewed_position_start=1&env=vp&impl=s&correlator=','Watch the video through to the end to bank this one.',15,'daily_task',4,'active');

INSERT INTO `settings` (`key`,`value`,`group`,`type`,`label`,`sort_order`) VALUES
('company_name','Global Ads','general','text','Company Name',1),
('company_tagline','Invest smart. Earn daily.','general','text','Tagline',2),
('logo','','general','image','Logo',3),
('favicon','','general','image','Favicon',4),
('currency_symbol','$','general','text','Currency Symbol',5),
('support_email','support@samrat.test','general','text','Support Email',6),
('support_telegram','','general','text','Telegram',7),
('footer_text','All rights reserved.','general','text','Footer Text',8),
('off_days','0','general','text','Weekly Off Days (0=Sun ... 6=Sat, comma separated)',9),
('withdrawal_fee_percent','5','finance','number','Withdrawal Fee (%)',1),
('withdrawal_enabled','1','finance','boolean','Withdrawals Enabled',3),
('deposit_enabled','1','finance','boolean','Deposits Enabled',4),
('referral_require_active_upline','1','finance','boolean','Pay only active upline accounts',5),
('referral_require_upline_investment','0','finance','boolean','Upline must hold an active plan to earn',6),
('agent_panel_enabled','1','agent','boolean','Agent Panel Enabled',1),
('agent_min_team_size','50','agent','number','Active Team Size Required to Apply',2),
('agent_team_depth','20','agent','number','Team Depth Limit (generations)',3),
('agent_deposit_percent','1','agent','number','Agent Commission on Team Deposits (%)',4),
('agent_profit_percent','0.5','agent','number','Agent Commission on Team Daily Profit (%)',5),
('registration_open','1','system','boolean','Registration Open',1),
('maintenance_mode','0','system','boolean','Maintenance Mode',2),
('maintenance_message','We are performing scheduled maintenance. Please check back soon.','system','textarea','Maintenance Message',3),
('cron_secret','154a4e0b3f11be75ac04c70089e4c9f0','system','text','Cron Secret Key',4),
('timezone','Asia/Dhaka','system','text','Timezone',5);

INSERT INTO `notices` (`title`,`slug`,`content`,`type`,`is_pinned`,`status`,`published_at`) VALUES
('Welcome','welcome','<p>Choose a package, deposit via USDT, complete your daily ads and earn every day.</p>','announcement',1,'published',NOW());
