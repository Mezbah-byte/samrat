-- ---------------------------------------------------------------------
-- Playable video ads, so /ads is never a list of empty boxes.
--
-- These are Google's public IMA sample VAST tags: real video creatives served
-- over a real ad request, which is what makes the watch flow testable without
-- a publisher account. They earn nothing - replace the tag on each row with
-- your own network's under Admin -> Ads once your account is approved.
--
-- Two details that decide whether a creative actually shows up:
--
--   deployment=devsite   the sample inventory only fills for this value; the
--                        tag without it comes back as an empty VAST.
--   sample_ct=...        only `linear` and `redirectlinear` still fill.
--                        `skippablelinear` and `linear_vpaid_2_js` are retired
--                        and answer empty, which the player then has to treat
--                        as "no fill".
--
-- `correlator` is left blank on purpose: app.js fills a fresh value per
-- request, because Google's ad server answers a repeated correlator with an
-- empty VAST.
--
--   mysql -u root samrat_db < database/seed_sample_video_ads.sql
--
-- Re-runnable: rows are matched by title, and an existing row is repointed at
-- the working tag rather than duplicated.
-- ---------------------------------------------------------------------

SET @head = 'https://pubads.g.doubleclick.net/gampad/ads?sz=640x480&iu=/21775744923/external/single_ad_samples&ciu_szs=300x250&impl=s&gdfp_req=1&env=vp&output=vast&unviewed_position_start=1&cust_params=deployment%3Ddevsite%26sample_ct%3D';
SET @tail = '&correlator=';

SET @linear   = CONCAT(@head, 'linear', @tail);
SET @redirect = CONCAT(@head, 'redirectlinear', @tail);

-- Four task ads. Same inventory, separate rows: the one-view-per-ad-per-day
-- rule is per row, so four rows is four slots of the daily quota.
INSERT INTO `ads` (`title`,`type`,`source`,`vast_url`,`body`,`watch_seconds`,`placement`,`sort_order`,`status`)
SELECT 'Video ad 1','video','vast',@linear,
       'Watch the video through to the end to bank this one.',15,'daily_task',1,'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `ads` WHERE `title` = 'Video ad 1');

INSERT INTO `ads` (`title`,`type`,`source`,`vast_url`,`body`,`watch_seconds`,`placement`,`sort_order`,`status`)
SELECT 'Video ad 2','video','vast',@redirect,
       'Watch the video through to the end to bank this one.',15,'daily_task',2,'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `ads` WHERE `title` = 'Video ad 2');

INSERT INTO `ads` (`title`,`type`,`source`,`vast_url`,`body`,`watch_seconds`,`placement`,`sort_order`,`status`)
SELECT 'Video ad 3','video','vast',@linear,
       'Watch the video through to the end to bank this one.',15,'daily_task',3,'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `ads` WHERE `title` = 'Video ad 3');

INSERT INTO `ads` (`title`,`type`,`source`,`vast_url`,`body`,`watch_seconds`,`placement`,`sort_order`,`status`)
SELECT 'Video ad 4','video','vast',@redirect,
       'Watch the video through to the end to bank this one.',15,'daily_task',4,'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `ads` WHERE `title` = 'Video ad 4');

-- One promo slot on the dashboard.
INSERT INTO `ads` (`title`,`type`,`source`,`vast_url`,`body`,`watch_seconds`,`placement`,`sort_order`,`status`)
SELECT 'Featured video','video','vast',@linear,
       'A short message from our sponsor.',15,'global',1,'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `ads` WHERE `title` = 'Featured video');

-- Repoint anything still on a retired sample tag, including rows seeded by an
-- older version of this file.
UPDATE `ads`
   SET `vast_url` = @linear
 WHERE `source` = 'vast'
   AND `vast_url` LIKE '%single_ad_samples%'
   AND (`vast_url` LIKE '%skippablelinear%'
     OR `vast_url` LIKE '%vpaid%'
     OR `vast_url` NOT LIKE '%deployment%3Ddevsite%');

-- The e2e fixtures carry no creative at all, so they can never be served.
UPDATE `ads` SET `status` = 'inactive' WHERE `title` LIKE 'E2E Ad%';
