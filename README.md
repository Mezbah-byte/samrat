# Samrat Investment Platform

CodeIgniter 3 application covering the **public site, user panel, admin panel and a minimal JSON API in one project**.

Users buy a package (fixed USDT deposit), earn a daily percentage for the plan's term as long as they complete
that day's advertisement quota, earn a one-time commission on several generations of their referral tree, and
withdraw subject to a package-based minimum and a percentage fee. Every one of those numbers is editable from the
admin panel.

---

## Requirements

| | |
|---|---|
| PHP | 7.2 – 8.2 (built and verified on 8.2.12) |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Web server | Apache with `mod_rewrite` |
| Extensions | `mysqli`, `mbstring`, `openssl`, `gd` or `exif` not required |

No Composer dependencies and no build step. Everything the browser needs is vendored under `assets/vendor/`,
so the app works with no internet access:

| Library | Used by |
|---|---|
| Bootstrap 5.3 | grid, dropdowns, modals, everywhere |
| Bootstrap Icons | admin panel |
| Lucide | user panel and public site |
| Chart.js 4 | dashboard earnings and allocation charts |
| GSAP + Draggable | the auth pages' pull-cord lamp |
| Rajdhani + Inter (woff2) | self-hosted fonts, latin subset |

---

## Install

1. **Copy the project** to your web root, e.g. `C:\xampp\htdocs\samrat`.

2. **Create the database and import the schema.** This drops and recreates `samrat_db`:

   ```bash
   mysql -u root < database/schema.sql
   ```

3. **Point the config at your database** if it is not XAMPP default — `application/config/database.php`:

   ```php
   'hostname' => 'localhost',
   'username' => 'root',
   'password' => '',
   'database' => 'samrat_db',
   ```

4. **Check the base URL.** `application/config/config.php` derives `base_url` from `HTTP_HOST` and assumes the app
   lives in a `/samrat/` subdirectory. If you deploy at a domain root, change both that line and `RewriteBase` in
   the root `.htaccess` to `/`.

5. **Make `uploads/` writable** by the web server.

6. Browse to **http://localhost/samrat**.

### Default credentials

| | |
|---|---|
| Admin panel | http://localhost/samrat/admin/login |
| Username | `admin` |
| Password | `Admin@123` |

**Change this password immediately** under *Admin → My Profile*.

---

## First-run checklist

The app ships with 9 packages seeded but is not ready for real users until you do these, all from the admin panel:

1. **Wallets** — replace the two placeholder addresses under *Admin → Wallets* with your real USDT receiving
   addresses and upload their QR codes. **Users cannot deposit until this is done**, and a wrong address here
   sends every deposit to the wrong place.
2. **Ads** — create at least as many active `daily_task` ads as your largest package's daily requirement.
   Without ads, nobody can unlock their daily profit. Each ad picks a **creative source**:

   | Source | What it is | When the quota clears |
   |---|---|---|
   | `upload` | your own image, or a video file / URL | countdown ends (a video must also finish) |
   | `embed` | an ad network's HTML/JS tag — Adsterra, PropellerAds, AdSense, Monetag… rendered in a sandboxed iframe | countdown ends |
   | `vast` | a VAST/VPAID tag played through Google IMA | the network's video reports `COMPLETE` |

   The installer ships one **inactive** sample VAST ad using Google's public IMA test tag — switch it on to prove
   the video path works, then replace the tag with your network's. `vast` ads need internet access: the IMA SDK is
   loaded from `imasdk.googleapis.com` on demand, and degrades to the countdown if it cannot load.
3. **Settings → General** — company name, logo, support email/Telegram.
4. **Settings → Finance** — withdrawal fee % (defaults to 5).
5. **Referral Levels** — the generation ladder. Ships as G1 5%, G2 2%, G3 1%, all paying. Add, edit or switch off
   generations here; this screen is the only place referral rates live.
6. **Packages** — packages 6–9 ship inactive as spare slots. Edit and activate the ones you want.
7. **Cron** — schedule the daily job (below).

---

## The daily cron

Run **once a day**. It closes out days whose ad quota was never met, opens today's earning rows, and completes
plans that have reached their end date.

```bash
php index.php cron run
```

Or over HTTP, using the secret from *Admin → Settings → System*:

```
http://localhost/samrat/cron/run?key=<cron_secret>
```

The job is **idempotent** — a unique index on `daily_earnings(investment_id, earn_date)` means running it twice,
or ten times, cannot pay anyone twice. It also backfills: if the server was down for three days, the next run
creates and resolves all three.

**Windows Task Scheduler** (daily at 00:05):

```
schtasks /create /tn "SamratDailyCron" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\samrat\index.php cron run" /sc daily /st 00:05
```

**Linux / cPanel crontab:**

```
5 0 * * * /usr/bin/php /home/USER/public_html/index.php cron run
```

---

## How the money works

**Deposit → active plan.** The user picks a package, sends USDT to the configured company wallet, and submits the
transaction hash. Nothing is credited yet. An admin verifies the hash on the block explorer (the deposit screen
links straight to Tronscan / BscScan / Etherscan) and approves. Approval runs as **one database transaction**:
the deposit is credited, the package cost is debited, the investment opens, day 1 is created, and the referral
tree is paid. If any step fails, none of it happens.

**Referral commission is paid by generation.** On approval the commission walks up the tree from the depositor:
their referrer is generation 1, that person's referrer generation 2, and so on for as many rows as
`referral_levels` holds. Each generation earns its own percentage of the **deposit amount**, once. The ladder is
edited under *Admin → Referral Levels*, along with two rules: whether a non-active account still earns, and
whether an upline must hold an active plan to earn. Switching a generation off does not cut the walk short — the
generations above it still earn. A unique index on `referral_commissions(deposit_id, level)` means a re-approval
can never pay the same generation twice.

**Ads unlock the daily profit.** Each day the user must watch `daily_ads` ads. Once the quota is met, that day's
`daily_amount` is credited. One view counts toward every active plan the user holds, so a user with two plans
watches the higher of the two requirements, not the sum.

**A missed day is lost.** The plan runs for a fixed number of calendar days. If the quota is not met before
midnight, the cron marks that day `missed` and the end date does not move — the income for that day is gone.

**Withdrawals hold funds immediately.** On submit, the amount leaves the balance and the request goes to the admin
queue. Approve → mark paid with the payout TXID. Reject → the full amount is refunded. This is why the same
balance cannot be requested twice while a request is under review.

**The ledger is the source of truth.** Every balance movement goes through `Wallet_lib`, which writes a
`transactions` row inside the same transaction that changes `users.balance`. Nothing else in the codebase updates
a balance. The admin user screen shows a live reconciliation — `SUM(transactions.amount)` against the stored
balance — so any drift is visible immediately.

---

## Layout

```
application/
  core/         MY_Controller.php  Public_/User_/Admin_/API_ base controllers
                MY_Model.php       shared CRUD + pagination
  libraries/    Wallet_lib.php     the only place balances change
                Investment_lib.php deposit approval, ads-to-profit, daily cron
                Auth_lib.php       registration, login, throttling, resets
                Uploader_lib.php   MIME-checked image uploads
  controllers/  public + user panel
    admin/      admin panel
    api/V1.php  JSON API
  models/       one per table
  views/
    layouts/    public / user / admin / auth shells
assets/         app.css, app.js, bundled Bootstrap
uploads/        avatars, deposits, ads, notices, qr, logo  (PHP execution blocked)
database/       schema.sql, e2e_test.sh
```

---

## API

Base path `/api/v1`. Auth is a bearer token on `users.api_token`, issued at login.

**Public**

| Method | Endpoint |
|---|---|
| POST | `/register` |
| POST | `/login` |
| GET | `/packages` |
| GET | `/notices` |
| GET | `/deposit/methods` |

**Authenticated** — send `Authorization: Bearer <token>`

| Method | Endpoint |
|---|---|
| GET | `/profile` |
| GET | `/dashboard` |
| GET | `/transactions` |
| GET | `/ads` |
| POST | `/ads/watch` |
| GET | `/referral` |
| POST | `/deposit` |
| POST | `/withdraw` |
| POST | `/logout` |

```bash
TOKEN=$(curl -s -X POST http://localhost/samrat/api/v1/login \
  -H 'Content-Type: application/json' \
  -d '{"identity":"someuser","password":"secret"}' | jq -r .data.token)

curl -s http://localhost/samrat/api/v1/dashboard -H "Authorization: Bearer $TOKEN"
```

Every response is `{ "success": bool, "message": string, "data": {...} }`.

The web panels do **not** go through the API — they use the models directly. The API exists for a future mobile
client, so `api/v1/*` is excluded from CSRF in `config.php`.

---

## Verification

`database/e2e_test.sh` drives the real HTTP endpoints with CSRF tokens, exactly as a browser would, and asserts
against the database. Requires the app running at `http://localhost/samrat` and `mysql` on the path.

```bash
bash database/e2e_test.sh
```

It covers registration and referral linking, admin login, wallet and ad creation, deposit submission, duplicate
TXID rejection, approval (investment + 5% commission + ledger balance), double-approval safety, the
ads-unlock-profit rule, replay protection on ad views, cron idempotency across two runs, the cron secret gate,
the withdrawal minimum, the 5% fee, approve/mark-paid, reject-and-refund, the API, and cross-panel session
isolation. It creates `e2e_ref` / `e2e_buyer` test accounts — delete them afterwards on a live install.

Last run: **55 passed, 0 failed**.

The flows were also driven manually through Chrome end to end — registration via a referral link,
admin wallet and ad setup, deposit submission and approval, the ads-unlock-profit modal, a paid
withdrawal, a rejected-and-refunded withdrawal, an admin balance adjustment, notice publishing, a
settings change propagating site-wide, and the cron HTTP endpoint. That pass surfaced three defects,
all fixed:

- `nice_date()` collided with CodeIgniter's own `date_helper` function, so the app's version was
  never defined and any call without an explicit format printed a raw Unix timestamp. The helper is
  now `fmt_date()`.
- The admin Transactions page summed raw ledger rows for "Withdrawn" and "Fees", which still counted
  requests that had been rejected and refunded. Both tiles now read from the status-filtered
  withdrawal stats.
- Rejecting a withdrawal refunded the gross amount against a `total_withdrawn` that had only been
  incremented by the net, leaving that figure short by the fee. The refund is now split so it mirrors
  the original debit exactly.

---

## Security notes

- Passwords use `password_hash` / bcrypt, with opportunistic rehash on login.
- CSRF protection is on for every web form; `api/v1/*` and `cron/*` are excluded by design.
- All queries go through the Query Builder or bound parameters.
- Uploads are validated by extension **and** by real MIME type, stored under randomised names, and
  `uploads/.htaccess` blocks PHP execution.
- Login throttling: 6 failed attempts per identity locks that identity for 15 minutes, tracked separately for
  users and admins.
- Admins live in their own table with their own session key, so a user session can never satisfy the admin guard.
- The last active super admin cannot be demoted or deleted.
- `/cron/run` over HTTP requires the shared secret, comparable with `hash_equals`.

### Before going live

- Change the default admin password and the `cron_secret`.
- Set `ENVIRONMENT` to `production` in `index.php` (currently `development`, which displays errors).
- Serve over HTTPS and set `$config['cookie_secure'] = TRUE`.
- Configure a real SMTP transport. Password reset currently **displays** the reset link on screen instead of
  emailing it, because no mail transport is configured — see `Auth::forgot_password`.
- Restrict database privileges to this one schema.

### A note on the business model

`project_details.txt` flagged this and it is worth repeating in the code repository: a guaranteed 2%/day return
for 100 days, combined with referral commissions and crypto deposits, is a high-risk financial structure. It
carries real exposure under payment-provider terms, banking and crypto compliance rules, and app-store and
consumer-protection policy in most jurisdictions. That is the operator's decision, not a technical one, but it
should be a deliberate decision rather than an accidental one.

---

## PHP 8.2 note

CodeIgniter 3.1.13 predates PHP 8.2 and its core emits `Creation of dynamic property` deprecation notices.
Rather than patching the framework, `index.php` sets the development error level to
`E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_STRICT`. Application code raises no notices at that level.
