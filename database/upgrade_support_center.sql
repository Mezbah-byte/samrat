-- ---------------------------------------------------------------------
-- Support centre.
--
-- Before: the only way to reach the team was the support email and the
--         Telegram link in the public footer. A logged-in user had nothing
--         at all - the panel never showed a contact anywhere.
-- After:  the user panel has a Support page (sidebar, avatar menu and a
--         dashboard card) listing every channel an admin has published,
--         and the public footer and about page read the same list.
--
-- The channels live in their own `support_links` table, not in `settings`:
-- the list is open-ended, each row carries a label, an icon, an order and an
-- on/off switch, and an admin can add a network the platform has never heard
-- of without a deploy. Only the page-level copy stays in `settings`.
--
--   mysql -u root samrat_db < database/upgrade_support_center.sql
--
-- Re-runnable: CREATE TABLE IF NOT EXISTS, INSERT IGNORE against the unique
-- label, and DELETE/INSERT IGNORE on settings keys.
-- ---------------------------------------------------------------------

-- 1. The channel table.
CREATE TABLE IF NOT EXISTS `support_links` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `label`      VARCHAR(60) NOT NULL,
  `kind`       ENUM('link','mailto','tel') NOT NULL DEFAULT 'link',
  `value`      VARCHAR(255) NOT NULL,
  `icon`       VARCHAR(40) NOT NULL DEFAULT 'life-buoy' COMMENT 'lucide icon name',
  `note`       VARCHAR(160) DEFAULT NULL COMMENT 'optional one-line hint under the channel',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_support_label` (`label`),
  KEY `ix_support_status_sort` (`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Carry over the two channels that already existed as settings rows, so an
--    install that had them configured does not lose them. Skipped when the
--    value was blank, and skipped again on a re-run by the unique label.
INSERT IGNORE INTO `support_links` (`label`,`kind`,`value`,`icon`,`sort_order`,`status`)
SELECT 'Email', 'mailto', s.`value`, 'mail', 1, 'active'
FROM `settings` s WHERE s.`key` = 'support_email' AND s.`value` <> '';

INSERT IGNORE INTO `support_links` (`label`,`kind`,`value`,`icon`,`sort_order`,`status`)
SELECT 'Telegram', 'link', s.`value`, 'send', 2, 'active'
FROM `settings` s WHERE s.`key` = 'support_telegram' AND s.`value` <> '';

-- 3. A fresh install with neither key set still needs something to show.
INSERT IGNORE INTO `support_links` (`label`,`kind`,`value`,`icon`,`note`,`sort_order`,`status`) VALUES
('Email','mailto','support@samrat.test','mail','Replies within one business day.',1,'active');

-- 4. Drop every per-channel settings key. They are rows in support_links now,
--    and the public footer and about page read the table too.
DELETE FROM `settings` WHERE `key` IN (
  'support_email','support_phone','support_telegram','support_whatsapp',
  'support_facebook','support_instagram','support_twitter','support_youtube',
  'support_tiktok','support_linkedin','support_discord','support_website'
);

-- 5. Close the gap the two removed keys left in the General tab.
UPDATE `settings` SET `sort_order` = 6 WHERE `key` = 'footer_text';
UPDATE `settings` SET `sort_order` = 7 WHERE `key` = 'off_days';

-- 6. The page-level copy that is not a channel.
INSERT IGNORE INTO `settings` (`key`,`value`,`group`,`type`,`label`,`sort_order`) VALUES
('support_enabled', '1', 'support', 'boolean',  'Support Page Enabled',                   1),
('support_hours',   '',  'support', 'text',     'Support Hours (e.g. Sat-Thu, 10am-7pm)', 2),
('support_note',    '',  'support', 'textarea', 'Note Shown Above the Channels',          3);

-- A re-run of an older copy of this script may have left these in the wrong
-- group or order; make it definite.
UPDATE `settings` SET `group` = 'support', `sort_order` = 1 WHERE `key` = 'support_enabled';
UPDATE `settings` SET `group` = 'support', `sort_order` = 2 WHERE `key` = 'support_hours';
UPDATE `settings` SET `group` = 'support', `sort_order` = 3 WHERE `key` = 'support_note';
