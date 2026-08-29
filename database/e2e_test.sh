#!/usr/bin/env bash
# End-to-end verification against the running app.
# Drives the real HTTP endpoints with CSRF tokens, exactly as a browser would.
set -u

BASE="http://localhost/samrat"
JAR_A="$(dirname "$0")/cookies_a.txt"   # user 1 (referrer)
JAR_B="$(dirname "$0")/cookies_b.txt"   # user 2 (referred)
JAR_X="$(dirname "$0")/cookies_admin.txt"
rm -f "$JAR_A" "$JAR_B" "$JAR_X"

pass=0; fail=0
ok()   { echo "  PASS  $1"; pass=$((pass+1)); }
bad()  { echo "  FAIL  $1"; fail=$((fail+1)); }
check(){ if [ "$2" = "$3" ]; then ok "$1 ($2)"; else bad "$1 (got '$2', want '$3')"; fi; }
# CI3 redirect() returns 303 on HTTP/1.1 and 302 on 1.0 - accept either.
redir(){ case "$2" in 302|303) ok "$1 ($2)";; *) bad "$1 (got '$2', want a redirect)";; esac; }

# Pull the csrf hidden field out of a page fetched with the given jar.
csrf() {
  curl -s -b "$1" -c "$1" "$2" \
    | grep -o 'name="csrf_test_name" value="[^"]*"' | head -1 \
    | sed 's/.*value="//;s/"//'
}

post() { # jar, url, page-to-get-token-from, data...
  local jar="$1" url="$2" tokpage="$3"; shift 3
  local t; t=$(csrf "$jar" "$tokpage")
  curl -s -b "$jar" -c "$jar" -o /dev/null -w "%{http_code}" \
       -X POST "$url" -d "csrf_test_name=$t" "$@"
}

# mysql.exe emits CRLF; strip trailing control chars or captured ids end up malformed.
sql() { "/c/xampp/mysql/bin/mysql.exe" -u root samrat_db -N -B -e "$1" | sed -e "s/[[:cntrl:]]*$//"; }

echo "=== 1. reset test data ==="
sql "DELETE FROM users WHERE username IN ('e2e_ref','e2e_buyer');"
sql "DELETE FROM ads WHERE title LIKE 'E2E Ad%';"
sql "DELETE FROM deposit_methods WHERE name='E2E Wallet';"
echo "  done"

echo
echo "=== 2. register referrer + referred user ==="
code=$(post "$JAR_A" "$BASE/register" "$BASE/register" \
  -d "full_name=E2E Referrer" -d "username=e2e_ref" -d "email=e2e_ref@test.local" \
  -d "mobile=01700000001" -d "country=Bangladesh" \
  -d "password=Passw0rd!" -d "confirm_password=Passw0rd!" -d "agree=1")
redir "register referrer" "$code"

REF_CODE=$(sql "SELECT referral_code FROM users WHERE username='e2e_ref';")
echo "  referrer code: $REF_CODE"

code=$(post "$JAR_B" "$BASE/register" "$BASE/register" \
  -d "full_name=E2E Buyer" -d "username=e2e_buyer" -d "email=e2e_buyer@test.local" \
  -d "mobile=01700000002" -d "country=Bangladesh" -d "referral_code=$REF_CODE" \
  -d "password=Passw0rd!" -d "confirm_password=Passw0rd!" -d "agree=1")
redir "register referred user" "$code"

LINKED=$(sql "SELECT COUNT(*) FROM users u JOIN users r ON r.id=u.referred_by WHERE u.username='e2e_buyer' AND r.username='e2e_ref';")
check "referral link recorded" "$LINKED" "1"

echo
echo "=== 3. admin login ==="
code=$(post "$JAR_X" "$BASE/admin/login" "$BASE/admin/login" \
  -d "identity=admin" -d "password=Admin@123")
redir "admin login redirect" "$code"
code=$(curl -s -b "$JAR_X" -c "$JAR_X" -o /dev/null -w "%{http_code}" "$BASE/admin/dashboard")
check "admin dashboard reachable" "$code" "200"

echo
echo "=== 4. admin seeds a wallet + 6 ads ==="
code=$(post "$JAR_X" "$BASE/admin/deposit-methods/create" "$BASE/admin/deposit-methods/create" \
  -d "name=E2E Wallet" -d "network=TRC20" -d "currency=USDT" \
  -d "wallet_address=TE2ETESTWALLETADDRESS1234567890" -d "min_amount=10" -d "status=active" -d "sort_order=9")
redir "create deposit wallet" "$code"

for i in 1 2 3 4 5 6; do
  code=$(post "$JAR_X" "$BASE/admin/ads/create" "$BASE/admin/ads/create" \
    -d "title=E2E Ad $i" -d "type=image" -d "placement=daily_task" \
    -d "watch_seconds=0" -d "sort_order=$i" -d "status=active")
  case "$code" in 302|303) ;; *) bad "create ad $i (got $code)";; esac
done
ADCOUNT=$(sql "SELECT COUNT(*) FROM ads WHERE title LIKE 'E2E Ad%' AND status='active';")
check "6 daily-task ads exist" "$ADCOUNT" "6"

echo
echo "=== 5. buyer submits a Silver deposit ==="
PKG=$(sql "SELECT id FROM packages WHERE slug='silver';")
DM=$(sql "SELECT id FROM deposit_methods WHERE name='E2E Wallet';")
TXID="e2etx$(date +%s)$RANDOM"
code=$(post "$JAR_B" "$BASE/deposit/create/$PKG" "$BASE/deposit/create/$PKG" \
  -d "deposit_method_id=$DM" -d "txid=$TXID")
redir "deposit submitted" "$code"

DEP_ID=$(sql "SELECT id FROM deposits WHERE txid='$TXID';")
DEP_STATUS=$(sql "SELECT status FROM deposits WHERE id=$DEP_ID;")
check "deposit is pending" "$DEP_STATUS" "pending"

echo "  -- duplicate TXID must be refused --"
code=$(post "$JAR_B" "$BASE/deposit/create/$PKG" "$BASE/deposit/create/$PKG" \
  -d "deposit_method_id=$DM" -d "txid=$TXID")
DUPES=$(sql "SELECT COUNT(*) FROM deposits WHERE txid='$TXID';")
check "duplicate TXID rejected" "$DUPES" "1"

echo
echo "=== 6. admin approves the deposit ==="
code=$(post "$JAR_X" "$BASE/admin/deposits/approve/$DEP_ID" "$BASE/admin/deposits/view/$DEP_ID" \
  -d "admin_note=verified on chain")
redir "approve posted" "$code"

check "deposit approved"        "$(sql "SELECT status FROM deposits WHERE id=$DEP_ID;")" "approved"
check "investment created"      "$(sql "SELECT COUNT(*) FROM investments WHERE deposit_id=$DEP_ID;")" "1"
check "investment daily amount" "$(sql "SELECT ROUND(daily_amount,2) FROM investments WHERE deposit_id=$DEP_ID;")" "1.00"
check "investment duration"     "$(sql "SELECT duration_days FROM investments WHERE deposit_id=$DEP_ID;")" "100"
check "referral commission 5%"  "$(sql "SELECT ROUND(amount,2) FROM referral_commissions WHERE deposit_id=$DEP_ID;")" "2.50"
check "referrer balance"        "$(sql "SELECT ROUND(balance,2) FROM users WHERE username='e2e_ref';")" "2.50"
check "buyer balance is 0"      "$(sql "SELECT ROUND(balance,2) FROM users WHERE username='e2e_buyer';")" "0.00"
check "buyer total_deposit"     "$(sql "SELECT ROUND(total_deposit,2) FROM users WHERE username='e2e_buyer';")" "50.00"
check "day-1 row opened"        "$(sql "SELECT COUNT(*) FROM daily_earnings de JOIN investments i ON i.id=de.investment_id WHERE i.deposit_id=$DEP_ID AND de.earn_date=CURDATE();")" "1"

echo "  -- ledger must equal stored balance for both users --"
check "buyer ledger balanced" \
  "$(sql "SELECT IF(ABS((SELECT IFNULL(SUM(amount),0) FROM transactions WHERE user_id=u.id) - u.balance) < 0.00000001,'yes','no') FROM users u WHERE u.username='e2e_buyer';")" "yes"
check "referrer ledger balanced" \
  "$(sql "SELECT IF(ABS((SELECT IFNULL(SUM(amount),0) FROM transactions WHERE user_id=u.id) - u.balance) < 0.00000001,'yes','no') FROM users u WHERE u.username='e2e_ref';")" "yes"

echo "  -- re-approving an already-approved deposit must not pay twice --"
post "$JAR_X" "$BASE/admin/deposits/approve/$DEP_ID" "$BASE/admin/deposits/view/$DEP_ID" -d "admin_note=retry" >/dev/null
check "still one investment"  "$(sql "SELECT COUNT(*) FROM investments WHERE deposit_id=$DEP_ID;")" "1"
check "still one commission"  "$(sql "SELECT COUNT(*) FROM referral_commissions WHERE deposit_id=$DEP_ID;")" "1"
check "referrer still 2.50"   "$(sql "SELECT ROUND(balance,2) FROM users WHERE username='e2e_ref';")" "2.50"

echo
echo "=== 7. buyer watches ads to unlock the daily profit ==="
BUYER_ID=$(sql "SELECT id FROM users WHERE username='e2e_buyer';")
REQ=$(sql "SELECT daily_ads FROM investments WHERE deposit_id=$DEP_ID;")
echo "  ads required today: $REQ"

n=0
for AD in $(sql "SELECT id FROM ads WHERE title LIKE 'E2E Ad%' ORDER BY sort_order LIMIT $REQ;"); do
  n=$((n+1))
  url="$BASE/ads/complete/$AD"
  code=$(post "$JAR_B" "$url" "$BASE/ads")
  rc=$?
  case "$code" in 302|303) ;; *) bad "watch ad $AD (http=$code curl_rc=$rc url=[$url])";; esac
  echo "    watched ad $AD -> day status now: $(sql "SELECT de.status FROM daily_earnings de JOIN investments i ON i.id=de.investment_id WHERE i.deposit_id=$DEP_ID AND de.earn_date=CURDATE();")"
done

check "day credited"         "$(sql "SELECT de.status FROM daily_earnings de JOIN investments i ON i.id=de.investment_id WHERE i.deposit_id=$DEP_ID AND de.earn_date=CURDATE();")" "credited"
check "buyer balance = 1.00" "$(sql "SELECT ROUND(balance,2) FROM users WHERE username='e2e_buyer';")" "1.00"
check "days_credited = 1"    "$(sql "SELECT days_credited FROM investments WHERE deposit_id=$DEP_ID;")" "1"
check "total_earned = 1.00"  "$(sql "SELECT ROUND(total_earned,2) FROM users WHERE username='e2e_buyer';")" "1.00"

echo "  -- re-watching the same ad must not pay again --"
FIRST_AD=$(sql "SELECT id FROM ads WHERE title LIKE 'E2E Ad%' ORDER BY sort_order LIMIT 1;")
post "$JAR_B" "$BASE/ads/complete/$FIRST_AD" "$BASE/ads" >/dev/null
check "balance still 1.00"   "$(sql "SELECT ROUND(balance,2) FROM users WHERE username='e2e_buyer';")" "1.00"
check "one ad_view per ad"   "$(sql "SELECT COUNT(*) FROM ad_views WHERE user_id=$BUYER_ID AND ad_id=$FIRST_AD AND view_date=CURDATE();")" "1"

echo
echo "=== 8. cron idempotency ==="
BEFORE=$(sql "SELECT COUNT(*) FROM daily_earnings;")
BEFORE_BAL=$(sql "SELECT ROUND(balance,2) FROM users WHERE username='e2e_buyer';")
( cd /c/xampp/htdocs/samrat && php index.php cron run >/dev/null 2>&1 )
( cd /c/xampp/htdocs/samrat && php index.php cron run >/dev/null 2>&1 )
AFTER=$(sql "SELECT COUNT(*) FROM daily_earnings;")
AFTER_BAL=$(sql "SELECT ROUND(balance,2) FROM users WHERE username='e2e_buyer';")
check "no duplicate day rows after 2 cron runs" "$AFTER" "$BEFORE"
check "balance unchanged by cron"               "$AFTER_BAL" "$BEFORE_BAL"

echo "  -- cron HTTP endpoint rejects a bad key --"
check "cron/run bad key" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/cron/run?key=wrong")" "403"
SECRET=$(sql "SELECT value FROM settings WHERE \`key\`='cron_secret';")
check "cron/run good key" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/cron/run?key=$SECRET")" "200"

echo
echo "=== 9. withdrawal: minimum, fee, approve, reject-refund ==="
sql "UPDATE users SET balance=100 WHERE username='e2e_buyer';"
sql "DELETE FROM transactions WHERE user_id=$BUYER_ID;"
sql "INSERT INTO transactions (user_id,type,amount,balance_after,description) VALUES ($BUYER_ID,'admin_credit',100,100,'e2e reset');"

echo "  -- below the Silver minimum (\$5) must be refused --"
post "$JAR_B" "$BASE/withdraw" "$BASE/withdraw" -d "amount=2" -d "network=TRC20" -d "wallet_address=TE2EDESTWALLET1234567890ABC" >/dev/null
check "sub-minimum refused" "$(sql "SELECT COUNT(*) FROM withdrawals WHERE user_id=$BUYER_ID;")" "0"

echo "  -- valid \$20 request --"
code=$(post "$JAR_B" "$BASE/withdraw" "$BASE/withdraw" -d "amount=20" -d "network=TRC20" -d "wallet_address=TE2EDESTWALLET1234567890ABC")
redir "withdrawal accepted" "$code"
WD=$(sql "SELECT id FROM withdrawals WHERE user_id=$BUYER_ID ORDER BY id DESC LIMIT 1;")
check "fee is 5% = 1.00"    "$(sql "SELECT ROUND(fee,2) FROM withdrawals WHERE id=$WD;")" "1.00"
check "net is 19.00"        "$(sql "SELECT ROUND(net_amount,2) FROM withdrawals WHERE id=$WD;")" "19.00"
check "balance held -> 80"  "$(sql "SELECT ROUND(balance,2) FROM users WHERE username='e2e_buyer';")" "80.00"
check "ledger balanced after hold" \
  "$(sql "SELECT IF(ABS((SELECT IFNULL(SUM(amount),0) FROM transactions WHERE user_id=u.id) - u.balance) < 0.00000001,'yes','no') FROM users u WHERE u.username='e2e_buyer';")" "yes"

echo "  -- approve then mark paid --"
post "$JAR_X" "$BASE/admin/withdrawals/approve/$WD" "$BASE/admin/withdrawals/view/$WD" -d "admin_note=ok" >/dev/null
check "status approved" "$(sql "SELECT status FROM withdrawals WHERE id=$WD;")" "approved"
post "$JAR_X" "$BASE/admin/withdrawals/mark_paid/$WD" "$BASE/admin/withdrawals/view/$WD" -d "txid=e2epayout123456" >/dev/null
check "status paid"     "$(sql "SELECT status FROM withdrawals WHERE id=$WD;")" "paid"
check "balance stays 80" "$(sql "SELECT ROUND(balance,2) FROM users WHERE username='e2e_buyer';")" "80.00"

echo "  -- second request, then reject: full refund --"
code=$(post "$JAR_B" "$BASE/withdraw" "$BASE/withdraw" -d "amount=10" -d "network=BEP20" -d "wallet_address=0xE2EDESTWALLET1234567890ABCDEF")
WD2=$(sql "SELECT id FROM withdrawals WHERE user_id=$BUYER_ID ORDER BY id DESC LIMIT 1;")
check "balance held -> 70" "$(sql "SELECT ROUND(balance,2) FROM users WHERE username='e2e_buyer';")" "70.00"
post "$JAR_X" "$BASE/admin/withdrawals/reject/$WD2" "$BASE/admin/withdrawals/view/$WD2" -d "admin_note=wrong address" >/dev/null
check "status rejected"      "$(sql "SELECT status FROM withdrawals WHERE id=$WD2;")" "rejected"
check "full 10 refunded -> 80" "$(sql "SELECT ROUND(balance,2) FROM users WHERE username='e2e_buyer';")" "80.00"
check "ledger balanced after refund" \
  "$(sql "SELECT IF(ABS((SELECT IFNULL(SUM(amount),0) FROM transactions WHERE user_id=u.id) - u.balance) < 0.00000001,'yes','no') FROM users u WHERE u.username='e2e_buyer';")" "yes"

echo
echo "=== 10. API ==="
TOKEN=$(curl -s -X POST "$BASE/api/v1/login" -H 'Content-Type: application/json' \
  -d '{"identity":"e2e_buyer","password":"Passw0rd!"}' \
  | grep -o '"token":"[^"]*"' | sed 's/.*://;s/"//g')
if [ -n "$TOKEN" ]; then ok "api login returned a token"; else bad "api login returned no token"; fi
check "api dashboard (with token)" "$(curl -s -o /dev/null -w '%{http_code}' -H "Authorization: Bearer $TOKEN" "$BASE/api/v1/dashboard")" "200"
check "api dashboard (no token)"   "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/api/v1/dashboard")" "401"
check "api packages (public)"      "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/api/v1/packages")" "200"
check "api notices (public)"       "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/api/v1/notices")" "200"

echo
echo "=== 11. cross-panel session isolation ==="
# JAR_B is a plain user; JAR_X is an admin.
check "user cannot reach /admin/dashboard" \
  "$(curl -s -b "$JAR_B" -o /dev/null -w '%{redirect_url}' "$BASE/admin/dashboard" | grep -c 'admin/login')" "1"
check "admin session cannot reach /dashboard" \
  "$(curl -s -b "$JAR_X" -o /dev/null -w '%{redirect_url}' "$BASE/dashboard" | grep -c '/login')" "1"

echo
echo "=================================================="
echo "  PASSED: $pass    FAILED: $fail"
echo "=================================================="
[ "$fail" -eq 0 ]
