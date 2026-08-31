-- ---------------------------------------------------------------------
-- Template: turn an approved ad network account into servable ad rows.
--
-- Nothing here works as-is. Every row carries a placeholder that has to be
-- replaced with a tag from YOUR publisher dashboard - a tag is tied to the
-- account and the domain it was issued for, so it cannot be shipped in a repo.
--
-- Two shapes, both already supported by the app:
--
--   source = 'embed'  the network's HTML/JS tag, rendered inside a sandboxed
--                     iframe. Countdown gates the confirm button.
--   source = 'vast'   a VAST/VPAID video tag played through Google IMA. The
--                     quota only clears on the SDK's COMPLETE event, so the
--                     user really watched the network's video.
--
-- Same thing can be done without SQL: Admin -> Ads -> New Ad, pick the source,
-- paste the tag. This file is for seeding several at once.
--
--   mysql -u root samrat_db < database/seed_network_ads_template.sql
-- ---------------------------------------------------------------------

-- --- 1. A display / banner tag (Adsterra, Monetag, HilltopAds, Adcash, ...) --
-- Replace the whole embed_code value with the snippet the dashboard gives you.
INSERT INTO `ads`
  (`title`,`type`,`source`,`embed_code`,`body`,`watch_seconds`,`placement`,`sort_order`,`status`)
VALUES
  ('Network banner 1','banner','embed',
   '<!-- PASTE YOUR NETWORK TAG HERE -->\n<script src="//network.example/tag.js" data-zone="REPLACE_ME"></script>',
   'Sponsored placement.',
   15,'daily_task',10,'inactive');

-- --- 2. A rewarded / in-stream VAST video tag -------------------------------
-- The zone has to be a VAST endpoint, not a page URL.
INSERT INTO `ads`
  (`title`,`type`,`source`,`vast_url`,`body`,`watch_seconds`,`placement`,`sort_order`,`status`)
VALUES
  ('Network video 1','video','vast',
   'https://network.example/vast?zone=REPLACE_ME&format=vast3',
   'Watch the video through to the end to bank this one.',
   15,'daily_task',11,'inactive');

-- Rows land as `inactive` on purpose: switch them on from Admin -> Ads only
-- after the tag has been checked once in the watch modal. An active row with a
-- dead tag burns a quota slot and pays nothing.
