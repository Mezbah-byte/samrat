# Serving real ads

The app already speaks three ad sources (`ads.source`), so no code change is
needed to go live - only an approved publisher account and its tag.

| source   | what you paste                | how the quota clears                        |
|----------|-------------------------------|---------------------------------------------|
| `upload` | your own image/video, or a URL | countdown (video must also reach its end)   |
| `embed`  | network HTML/JS tag           | countdown, tag runs in a sandboxed iframe   |
| `vast`   | VAST/VPAID tag URL            | Google IMA `COMPLETE` event - a real watch  |

Admin -> Ads -> New Ad, pick the source, paste, save. `database/seed_network_ads_template.sql`
does the same in bulk.

## What an account actually needs

1. A live domain serving the site over HTTPS (an IP or a staging URL is rejected).
2. Privacy Policy, Terms and a contact page reachable from the footer.
3. Real traffic. Most low-barrier networks have no hard minimum, but an empty
   site gets rejected on review.
4. A payout method (Payoneer / bank / crypto, network-dependent) and, for some,
   a tax form.
5. Review time: hours to a few days for the low-barrier networks, longer for
   the premium ones.

## The rule that decides which network can be used

This platform **pays users to watch ads**. That is incentivised traffic, and the
ordinary display networks forbid it in their terms - Google AdSense explicitly,
and Adsterra / Monetag / PropellerAds / HilltopAds / Adcash in substance. Running
their tag here risks the account being closed with the earned balance unpaid,
which is the expensive failure mode: the traffic is spent and nothing is
collected.

The networks built for this model are the **rewarded video / offerwall** ones,
where paying the user is the point:

- AdGate Media
- AdGem
- Ayet-Studios
- Torox
- CPX Research, BitLabs (survey walls)

Most of them hand out a rewarded VAST tag (drop it in as `source = 'vast'`) or
an iframe offerwall (`source = 'embed'`), plus a server-to-server postback so
the reward is credited from their callback rather than from the browser.

Note that many of these also decline investment / HYIP-style sites during
review. Read the network's own vertical restrictions before building the
integration, and describe the site honestly in the application - a site
approved on a false description is closed later, after the traffic is spent.

## Postback (server-to-server) - not built yet

Rewarded networks confirm a completed view by calling a URL on this server,
signed with a shared secret. The current flow credits on the browser's confirm
instead. If you go with a rewarded network, the missing piece is a
`POST /callback/<network>` endpoint that verifies the signature and calls
`Investment_lib::register_ad_view()`. Ask and it can be added.

## Running real ads without an account (zero revenue)

If the point is only that users see a real video ad, no publisher account and no
network application is needed:

    mysql -u root samrat_db < database/seed_sample_video_ads.sql

That seeds five rows against Google's public IMA sample inventory - a real ad
request answered with a real video creative, played through the IMA SDK, with
the quota clearing only on `COMPLETE`. It pays nothing, and it never will; it is
the demo inventory, not a monetised placement.

Two things decide whether a creative actually appears, both already handled by
the seed and the player:

- `deployment=devsite` in `cust_params`. Without it the sample inventory answers
  with an empty VAST.
- A unique `correlator` per request. Google's ad server dedupes on it, so a tag
  pasted with `correlator=` left blank returned an ad on the first watch and
  nothing afterwards. `app.js` now fills a fresh value on every request.

Only `sample_ct=linear` and `sample_ct=redirectlinear` still fill.
`skippablelinear` and `linear_vpaid_2_js` are retired and answer empty, which
the player has to read as no fill - the seed repoints any row still on them.

When a real network is added later, replacing the `vast_url` on these rows in
Admin -> Ads is the whole migration.
