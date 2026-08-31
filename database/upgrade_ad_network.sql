-- ---------------------------------------------------------------------
-- Ads served from a real ad network.
--
-- Before: every ad was an uploaded image with a countdown next to it.
-- After:  an ad row also carries where its creative comes from -
--           upload  - an image or a video file (uploaded, or a URL)
--           embed   - a network's own HTML/JS tag (Adsterra, PropellerAds,
--                     AdSense, Monetag, ...), rendered in a sandboxed iframe
--           vast    - a VAST/VPAID video tag played through Google IMA, so the
--                     quota only clears when the network's video really ends
--
--   mysql -u root samrat_db < database/upgrade_ad_network.sql
--
-- Existing rows keep working: they default to `upload`, which is what they were.
-- Re-runnable: each column is added only when it is missing, so a database
-- already built from database/schema.sql just skips them.
-- ---------------------------------------------------------------------

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `ads` ADD COLUMN `source` ENUM(''upload'',''embed'',''vast'') NOT NULL DEFAULT ''upload'' COMMENT ''where the creative comes from'' AFTER `type`',
  'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ads' AND COLUMN_NAME = 'source');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `ads` ADD COLUMN `media_url` VARCHAR(500) DEFAULT NULL COMMENT ''remote image or video file'' AFTER `media`',
  'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ads' AND COLUMN_NAME = 'media_url');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `ads` ADD COLUMN `embed_code` TEXT DEFAULT NULL COMMENT ''ad network HTML/JS tag'' AFTER `body`',
  'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ads' AND COLUMN_NAME = 'embed_code');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `ads` ADD COLUMN `vast_url` VARCHAR(500) DEFAULT NULL COMMENT ''VAST/VPAID tag URL'' AFTER `embed_code`',
  'SELECT 1')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ads' AND COLUMN_NAME = 'vast_url');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Google's public IMA test tag. Inactive on purpose: switch it on to prove the
-- video path works end to end before wiring a paying network in.
-- Inserted only once, however many times this file runs.
INSERT INTO `ads` (`title`,`type`,`source`,`vast_url`,`body`,`watch_seconds`,`placement`,`sort_order`,`status`)
SELECT 'Sample network video (IMA test tag)','video','vast',
 'https://pubads.g.doubleclick.net/gampad/ads?iu=/21775744923/external/single_ad_samples&sz=640x480&cust_params=sample_ct%3Dlinear&ciu_szs=300x250%2C728x90&gdfp_req=1&output=vast&unviewed_position_start=1&env=vp&impl=s&correlator=',
 'Test creative from Google IMA. Replace the VAST tag with your own network before going live.',
 15,'daily_task',99,'inactive'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `ads` WHERE `title` = 'Sample network video (IMA test tag)');
