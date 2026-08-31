-- ---------------------------------------------------------------------
-- Weekly off day.
--
-- Before: ads ran every day of the week.
-- After:  the weekdays listed in `settings.off_days` serve no ads at all -
--         no daily-task list, no global promo slots, and a POST to
--         ads/complete is rejected. No day row is opened for an off day
--         either, so the user is not marked "missed" for a day the platform
--         itself was closed.
--
-- Values are PHP date('w') numbers, comma separated: 0 = Sunday .. 6 = Saturday.
-- '0' means Sunday off. An empty value turns the feature off entirely.
-- Editable from Admin -> Settings -> General.
--
--   mysql -u root samrat_db < database/upgrade_off_days.sql
--
-- Re-runnable: INSERT IGNORE leaves an already configured value alone.
-- ---------------------------------------------------------------------

INSERT IGNORE INTO `settings` (`key`,`value`,`group`,`type`,`label`,`sort_order`) VALUES
('off_days','0','general','text','Weekly Off Days (0=Sun ... 6=Sat, comma separated)',9);
