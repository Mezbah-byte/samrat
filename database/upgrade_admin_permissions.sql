-- ---------------------------------------------------------------------
-- Granular admin permissions.
--
-- Before: the admin panel authorised on the `admins.role` string alone, and
--         only some controller methods checked it. Every method that forgot
--         the check was open to every signed-in admin - a `moderator` could
--         approve deposits and withdrawals, edit users and broadcast
--         notifications. A super admin also had no way to say "this account
--         may approve deposits but not adjust balances".
-- After:  each account carries its own set of `module.action` grants. The
--         role now only picks the checkboxes a new account starts with; the
--         rows in this table are the authority. A `super_admin` bypasses the
--         check entirely and therefore stores no rows.
--
--   mysql -u root samrat_db < database/upgrade_admin_permissions.sql
--
-- Re-runnable: CREATE TABLE IF NOT EXISTS plus INSERT IGNORE against the
-- composite primary key, so a second run adds nothing and errors on nothing.
-- An account that already holds any grant is skipped entirely, so a matrix
-- a super admin has tuned by hand is never re-seeded back to its preset.
-- ---------------------------------------------------------------------

-- 1. The grant table.
CREATE TABLE IF NOT EXISTS `admin_permissions` (
  `admin_id`   INT UNSIGNED NOT NULL,
  `permission` VARCHAR(60) NOT NULL COMMENT 'module.action, see application/config/permissions.php',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`, `permission`),
  CONSTRAINT `fk_admin_permissions_admin`
    FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Which accounts already carry grants. Snapshotted into a temp table
--    because MySQL will not let an INSERT ... SELECT read its own target,
--    and taken before either backfill so neither run sees the other's rows.
DROP TEMPORARY TABLE IF EXISTS `tmp_seeded_admins`;
CREATE TEMPORARY TABLE `tmp_seeded_admins` AS
SELECT DISTINCT `admin_id` FROM `admin_permissions`;

-- 3. Backfill the `admin` role with the day-to-day operations set.
--    Deliberately excluded: admins.*, agents.*, settings.cron_secret and
--    agent_applications.approve - those stay with the super admin. Approving
--    hands off to Agents::create(), which needs agents.manage anyway.
INSERT IGNORE INTO `admin_permissions` (`admin_id`, `permission`)
SELECT a.`id`, p.`perm`
FROM `admins` a
JOIN (
  SELECT 'deposits.view' AS `perm`
  UNION ALL SELECT 'deposits.approve'
  UNION ALL SELECT 'deposits.reject'
  UNION ALL SELECT 'withdrawals.view'
  UNION ALL SELECT 'withdrawals.approve'
  UNION ALL SELECT 'withdrawals.mark_paid'
  UNION ALL SELECT 'withdrawals.reject'
  UNION ALL SELECT 'investments.view'
  UNION ALL SELECT 'investments.cancel'
  UNION ALL SELECT 'transactions.view'
  UNION ALL SELECT 'referrals.view'
  UNION ALL SELECT 'referral_levels.view'
  UNION ALL SELECT 'referral_levels.manage'
  UNION ALL SELECT 'team_bonus.view'
  UNION ALL SELECT 'team_bonus.manage'
  UNION ALL SELECT 'team_bonus.recompute'
  UNION ALL SELECT 'users.view'
  UNION ALL SELECT 'users.edit'
  UNION ALL SELECT 'users.adjust'
  UNION ALL SELECT 'users.status'
  UNION ALL SELECT 'users.impersonate'
  UNION ALL SELECT 'agent_applications.view'
  UNION ALL SELECT 'agent_applications.reject'
  UNION ALL SELECT 'agent_applications.nid'
  UNION ALL SELECT 'packages.view'
  UNION ALL SELECT 'packages.manage'
  UNION ALL SELECT 'deposit_methods.view'
  UNION ALL SELECT 'deposit_methods.manage'
  UNION ALL SELECT 'ads.view'
  UNION ALL SELECT 'ads.manage'
  UNION ALL SELECT 'notices.view'
  UNION ALL SELECT 'notices.manage'
  UNION ALL SELECT 'notifications.view'
  UNION ALL SELECT 'notifications.send'
  UNION ALL SELECT 'support_links.view'
  UNION ALL SELECT 'support_links.manage'
  UNION ALL SELECT 'settings.view'
  UNION ALL SELECT 'settings.manage'
  UNION ALL SELECT 'logs.view'
) p
LEFT JOIN `tmp_seeded_admins` g ON g.`admin_id` = a.`id`
WHERE a.`role` = 'admin'
  AND g.`admin_id` IS NULL;

-- 4. Backfill the `moderator` role as view-only. This is the behaviour change:
--    a moderator that could previously approve a deposit no longer can.
--    Settings, admin accounts and agents are not even visible.
INSERT IGNORE INTO `admin_permissions` (`admin_id`, `permission`)
SELECT a.`id`, p.`perm`
FROM `admins` a
JOIN (
  SELECT 'deposits.view' AS `perm`
  UNION ALL SELECT 'withdrawals.view'
  UNION ALL SELECT 'investments.view'
  UNION ALL SELECT 'transactions.view'
  UNION ALL SELECT 'referrals.view'
  UNION ALL SELECT 'referral_levels.view'
  UNION ALL SELECT 'team_bonus.view'
  UNION ALL SELECT 'users.view'
  UNION ALL SELECT 'agent_applications.view'
  UNION ALL SELECT 'packages.view'
  UNION ALL SELECT 'deposit_methods.view'
  UNION ALL SELECT 'ads.view'
  UNION ALL SELECT 'notices.view'
  UNION ALL SELECT 'notifications.view'
  UNION ALL SELECT 'support_links.view'
  UNION ALL SELECT 'logs.view'
) p
LEFT JOIN `tmp_seeded_admins` g ON g.`admin_id` = a.`id`
WHERE a.`role` = 'moderator'
  AND g.`admin_id` IS NULL;

-- 5. A super admin is authorised by bypass, so any row it holds is dead weight
--    and would only mislead whoever reads this table later.
DELETE ap FROM `admin_permissions` ap
JOIN `admins` a ON a.`id` = ap.`admin_id`
WHERE a.`role` = 'super_admin';

DROP TEMPORARY TABLE IF EXISTS `tmp_seeded_admins`;
