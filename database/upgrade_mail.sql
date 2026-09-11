-- ---------------------------------------------------------------------
-- Mailbox management.
--
-- Before: the platform had no mail transport at all. The forgot-password
--         flow wrote a reset link into the database and told the admin to
--         hand it over by some other channel.
-- After:  an admin can register every mailbox that exists on a company
--         domain, open any of them from inside the panel, and read, reply,
--         file and delete mail without leaving it.
--
-- The mailboxes are NOT created here. A mailbox lives on the mail server,
-- and this application only ever talks to that server as a client - IMAP to
-- read, SMTP to send. Creating one stays a cPanel job. What a row in
-- `mail_accounts` records is the credential set the panel logs in with.
--
-- `mail_domains` is what makes adding the second mailbox cheap: the IMAP and
-- SMTP endpoints are a property of the domain, not of the mailbox, so they
-- are stored once and every account under the domain inherits them.
--
--   mysql -u root samrat_db < database/upgrade_mail.sql
--
-- Re-runnable: CREATE TABLE IF NOT EXISTS throughout.
-- ---------------------------------------------------------------------

-- 1. One row per mail domain, carrying the endpoints its mailboxes share.
CREATE TABLE IF NOT EXISTS `mail_domains` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `domain`             VARCHAR(190) NOT NULL COMMENT 'bare domain, no scheme, no @',
  `imap_host`          VARCHAR(190) NOT NULL,
  `imap_port`          SMALLINT UNSIGNED NOT NULL DEFAULT 993,
  `imap_encryption`    ENUM('ssl','tls','none') NOT NULL DEFAULT 'ssl',
  `imap_validate_cert` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0 only for a self-signed host',
  `smtp_host`          VARCHAR(190) NOT NULL,
  `smtp_port`          SMALLINT UNSIGNED NOT NULL DEFAULT 465,
  `smtp_encryption`    ENUM('ssl','tls','none') NOT NULL DEFAULT 'ssl',
  `sent_folder`        VARCHAR(120) NOT NULL DEFAULT 'INBOX.Sent' COMMENT 'where a sent copy is appended',
  `trash_folder`       VARCHAR(120) NOT NULL DEFAULT 'INBOX.Trash',
  `status`             ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `note`               VARCHAR(255) DEFAULT NULL,
  `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mail_domain` (`domain`),
  KEY `ix_mail_domain_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. One row per mailbox.
--
--    `password_enc` is reversible by necessity: IMAP LOGIN needs the cleartext
--    password on every connection, so a one-way hash cannot be used here the
--    way it is for an admin account. It is AES-encrypted with a key that lives
--    outside the repository (application/config/secrets.php, gitignored), so a
--    database dump on its own does not surrender the mailboxes.
CREATE TABLE IF NOT EXISTS `mail_accounts` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `domain_id`       INT UNSIGNED NOT NULL,
  `local_part`      VARCHAR(120) NOT NULL COMMENT 'the part before the @',
  `email`           VARCHAR(190) NOT NULL COMMENT 'local_part@domain, stored whole for lookups',
  `display_name`    VARCHAR(120) DEFAULT NULL COMMENT 'From: name on outgoing mail',
  `password_enc`    TEXT NOT NULL COMMENT 'AES-encrypted; see Crypto_lib',
  `signature`       TEXT DEFAULT NULL COMMENT 'appended to composed mail',
  `status`          ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `last_checked_at` DATETIME DEFAULT NULL COMMENT 'last successful IMAP connect',
  `last_error`      VARCHAR(255) DEFAULT NULL COMMENT 'why the last connect failed',
  `created_by`      INT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mail_account_email` (`email`),
  KEY `ix_mail_account_domain` (`domain_id`,`status`),
  CONSTRAINT `fk_mail_account_domain` FOREIGN KEY (`domain_id`)
    REFERENCES `mail_domains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mail_account_admin` FOREIGN KEY (`created_by`)
    REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Who opened which mailbox, and what they did in it.
--
--    Separate from `admin_logs` on purpose. Reading someone else's mail is a
--    different class of act from approving a deposit, and it needs a trail
--    that can be shown per mailbox rather than searched out of the general
--    activity feed.
CREATE TABLE IF NOT EXISTS `mail_access_logs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`   INT UNSIGNED DEFAULT NULL,
  `account_id` INT UNSIGNED DEFAULT NULL,
  `action`     VARCHAR(40) NOT NULL COMMENT 'open|read|send|delete|move|flag|create|update|remove|test',
  `detail`     VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_mail_log_account` (`account_id`,`created_at`),
  KEY `ix_mail_log_admin` (`admin_id`,`created_at`),
  CONSTRAINT `fk_mail_log_account` FOREIGN KEY (`account_id`)
    REFERENCES `mail_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mail_log_admin` FOREIGN KEY (`admin_id`)
    REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
