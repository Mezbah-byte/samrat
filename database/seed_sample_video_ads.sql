-- ---------------------------------------------------------------------
-- Playable video ads, so /ads is never a list of empty boxes.
--
-- These are Google's public IMA sample VAST tags: real video creatives served
-- over a real ad request, which is what makes the watch flow testable without
-- a publisher account. They earn nothing - replace the tag on each row with
-- your own network's under Admin -> Ads once your account is approved.
--
--   mysql -u root samrat_db < database/seed_sample_video_ads.sql
-- ---------------------------------------------------------------------

SET @tag = 'https://pubads.g.doubleclick.net/gampad/ads?iu=/21775744923/external/single_ad_samples&sz=640x480&cust_params=sample_ct%3D';
SET @tail = '&ciu_szs=300x250%2C728x90&gdfp_req=1&output=vast&unviewed_position_start=1&env=vp&impl=s&correlator=';

INSERT INTO `ads` (`title`,`type`,`source`,`vast_url`,`body`,`watch_seconds`,`placement`,`sort_order`,`status`) VALUES
('Video ad 1','video','vast', CONCAT(@tag,'linear',@tail),
 'Watch the video through to the end to bank this one.',15,'daily_task',1,'active'),
('Video ad 2','video','vast', CONCAT(@tag,'skippablelinear',@tail),
 'Watch the video through to the end to bank this one.',15,'daily_task',2,'active'),
('Video ad 3','video','vast', CONCAT(@tag,'redirectlinear',@tail),
 'Watch the video through to the end to bank this one.',15,'daily_task',3,'active'),
('Video ad 4','video','vast', CONCAT(@tag,'linear',@tail),
 'Watch the video through to the end to bank this one.',15,'daily_task',4,'active');

-- The e2e fixtures carry no creative at all, so they can never be served.
UPDATE `ads` SET `status` = 'inactive' WHERE `title` LIKE 'E2E Ad%';
