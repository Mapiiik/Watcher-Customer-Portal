#!/usr/bin/env bash
# Runs the production image of the portal against the development Watcher CRM, once with the
# default profile and once with every profile in data/*/profile: signing in and out, attacks on
# the forms, every page with all errors shown, the consents, a change request (caught by Mailpit),
# the password and the old addresses. Everything it creates is removed again.
#
# Usage: tests/run.sh
# Needs the Watcher CRM development containers (web server, database and their network) running.
set -u
ROOT=$(cd "$(dirname "$0")/.." && pwd)
DB_CONTAINER=${DEV_DB_CONTAINER:-watcher-crm-database-dev-1}
CRM_CONTAINER=${DEV_CRM_CONTAINER:-watcher-crm-webserver-dev-1}
NETWORK=${DEV_NETWORK:-watcher-crm_default}
# the database the development CRM works with, where its test login and API user go
DEV_DB=${DEV_DB:-watcher_crm}
# the development web server answers only to localhost, so the container carries its port there
CRM_HOST=${DEV_CRM_HOST:-webserver-dev:443}
PORT=${PORT:-18443}
RUN=customer-portal-test
MAIL=customer-portal-mailpit
TMP="$ROOT/tests/.tmp"
PASSWORD=Test-Password-2026
dk() { MSYS_NO_PATHCONV=1 docker "$@"; }
psql() { dk exec -i "$DB_CONTAINER" psql -U postgres -q -v ON_ERROR_STOP=1 "$@"; }
random() { head -c "$1" /dev/urandom | od -An -tx1 | tr -d ' \n'; }
host_path() { if command -v cygpath >/dev/null; then cygpath -w "$1"; else echo "$1"; fi; }

cd "$ROOT" || exit 1
API_KEY=$(random 24)
SHA=$(printf '%s' "$PASSWORD" | sha1sum | cut -c1-40)
FAILED=0
ok() { printf "  %-60s %s\n" "$1" "$2"; }
fail() { printf "FAIL %-56s %s\n" "$1" "$2"; FAILED=1; }

cleanup() {
  dk rm -f $RUN $MAIL >/dev/null 2>&1
  # the API user stays, as what it changed names it, but without a key that works
  psql -d "$DEV_DB" -c "DELETE FROM logins WHERE login = 'zz-portal-test'" \
    -c "UPDATE users SET active = false, api_token = NULL WHERE username = 'zz-portal-test'" >/dev/null
  rm -rf "$TMP"
}
trap cleanup EXIT
cleanup
mkdir -p "$TMP"

echo "== build"
dk build -q -f docker/Dockerfile.production -t watcher-customer-portal:local . >/dev/null || exit 1
dk build -q -t watcher-customer-portal:test tests >/dev/null || exit 1

psql -d "$DEV_DB" -c "INSERT INTO users (username, password, active, role, created, modified)
  SELECT 'zz-portal-test', '!', false, 'customer-portal', now(), now()
  WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'zz-portal-test')" \
  -c "UPDATE users SET active = true, role = 'customer-portal', api_token = '$API_KEY' WHERE username = 'zz-portal-test'" >/dev/null
dk run -d --name $MAIL --network "$NETWORK" axllent/mailpit:v1.27 >/dev/null || exit 1

# the default profile, then each one an operator keeps in data/
PROFILES="default $(ls -d data/*/profile 2>/dev/null | cut -d/ -f2 | tr '\n' ' ')"
for PROFILE in $PROFILES; do
  echo "== profile $PROFILE"
  dk rm -f $RUN >/dev/null 2>&1
  # the CRM counts the failed sign-ins this walk makes on purpose, and would block the next ones
  dk exec "$CRM_CONTAINER" bin/cake cache clear default >/dev/null
  psql -d "$DEV_DB" -c "DELETE FROM logins WHERE login = 'zz-portal-test'" -c "INSERT INTO logins (id, login, password, rights, locked, customer_id, created, modified)
    SELECT uuid_generate_v4(), 'zz-portal-test', '$SHA', 1, false,
      (SELECT customer_id FROM invoices GROUP BY customer_id ORDER BY count(*) DESC LIMIT 1), now(), now()" >/dev/null
  CUSTOMER="(SELECT customer_id FROM logins WHERE login = 'zz-portal-test')"
  MOUNT=()
  [ "$PROFILE" != default ] && MOUNT=(-v "$(host_path "$ROOT/data/$PROFILE/profile"):/app/profile:ro")
  # the language and currency an operator's deployment sets, the defaults (en_US, EUR) otherwise
  [ -f "data/$PROFILE/test.env" ] && MOUNT+=(--env-file "data/$PROFILE/test.env")
  dk run -d --name $RUN --network "$NETWORK" -p $PORT:443 "${MOUNT[@]}" \
    -e SERVER_NAME=localhost -e OPERATOR_NAME="Test Operator, s.r.o." -e OPERATOR_WEBSITE=https://www.example.net \
    -e PRIVACY_POLICY_URL=https://www.example.net/privacy -e SUPPORT_PHONE="+420 000 000 000" -e SUPPORT_EMAIL=support@example.net \
    -e PAYMENT_ACCOUNT=123456789/0100 \
    -e CRM_API_URL=https://localhost:8443/api/customer-portal -e CRM_API_KEY="$API_KEY" -e CRM_API_VERIFY_TLS=false \
    -e SMTP_HOST=$MAIL -e SMTP_PORT=1025 -e SMTP_SECURE=none -e MAIL_FROM=portal@example.net \
    -e CHANGE_REQUESTS_TO=contracts@example.invalid \
    watcher-customer-portal:test >/dev/null || exit 1
  dk exec -d $RUN socat TCP-LISTEN:8443,fork,reuseaddr TCP:$CRM_HOST
  sleep 3

  B=https://localhost:$PORT
  J="$TMP/cookies"
  H="$TMP/headers"
  rm -f "$J"
  c() { curl -sk -b "$J" -c "$J" "$@"; }
  page() { c -D "$H" -o "$TMP/body" -w '%{http_code}' "$B$1"; }
  token() { grep -o 'name="_csrfToken"[^>]*value="[^"]*"' "$TMP/body" | head -1 | sed 's/.*value="//; s/"$//'; }
  problems() { sed -e 's/<[^>]*>//g' "$TMP/body" | grep -c -i -E "warning|fatal|deprecated|notice|uncaught|not available right now|nije dostupan|není dostupný"; }

  echo "-- signing in"
  code=$(page /invoices)
  [ "$code" = 302 ] && grep -qi '^location: .*/sign-in' "$H" && ok "a page without signing in" "sends to signing in" || fail "a page without signing in" "$code"
  grep -qi "^set-cookie: __Host-portal=.*path=/; secure; HttpOnly; SameSite=Lax" "$H" && ok "the session cookie" "__Host-, Secure, HttpOnly, Lax" || fail "the session cookie" "flags"
  code=$(page /sign-in)
  grep -qi "^content-security-policy: default-src 'self'; img-src" "$H" && ok "the content security policy" "no inline" || fail "the content security policy" "missing"
  grep -qi '^cache-control: no-store' "$H" && ok "the sign-in page" "no-store" || fail "the sign-in page" "is cacheable"
  grep -q '<script\|style="' "$TMP/body" && fail "the sign-in page" "has inline script or style"
  T=$(token)
  code=$(c -o /dev/null -w '%{http_code}' -H "Origin: https://attacker.example" --data-urlencode "_csrfToken=$T" \
    --data-urlencode login=zz-portal-test --data-urlencode password=$PASSWORD "$B/sign-in")
  [ "$code" = 403 ] && ok "a sign-in from another site" "403" || fail "a sign-in from another site" "$code"
  code=$(c -o /dev/null -w '%{http_code}' --data-urlencode login=zz-portal-test --data-urlencode password=$PASSWORD "$B/sign-in")
  [ "$code" = 403 ] && ok "a sign-in without the session token" "403" || fail "a sign-in without the session token" "$code"
  code=$(c -o "$TMP/body" -w '%{http_code}' --data-urlencode "_csrfToken=$T" --data-urlencode login=zz-portal-test \
    --data-urlencode password=wrong "$B/sign-in")
  [ "$code" = 200 ] && grep -q 'class="alert error"' "$TMP/body" && ok "a wrong password" "refused" || fail "a wrong password" "$code"
  BEFORE=$(grep __Host-portal "$J" | awk '{print $NF}')
  code=$(c -D "$H" -o /dev/null -w '%{http_code}' --data-urlencode "_csrfToken=$T" --data-urlencode login=zz-portal-test \
    --data-urlencode password=$PASSWORD "$B/sign-in")
  AFTER=$(grep __Host-portal "$J" | awk '{print $NF}')
  [ "$code" = 302 ] || [ "$code" = 303 ] && ok "the right password" "signed in ($code)" || fail "the right password" "$code"
  [ "$BEFORE" != "$AFTER" ] && ok "the session id" "renewed on signing in" || fail "the session id" "kept on signing in"
  [ "$(psql -d "$DEV_DB" -Atc "SELECT left(password, 10) FROM logins WHERE login = 'zz-portal-test'")" = '$argon2id$' ] \
    && ok "the stored password" "Argon2id" || fail "the stored password" "not Argon2id"

  echo "-- pages"
  for p in / /invoices /details /details/change /password; do
    code=$(page "$p")
    bad=$(problems)
    printf "  %-60s %s problems=%s\n" "$p" "$code" "$bad"
    [ "$code" = 200 ] && [ "$bad" = 0 ] || FAILED=1
    grep -q '<script\|style="' "$TMP/body" && fail "$p" "has inline script or style"
  done
  page /details >/dev/null
  grep -q 'name="agree_mailing_billing"' "$TMP/body" && ok "/details" "shows the consents" || fail "/details" "has no consents"
  case "$PROFILE" in
    netair) page / >/dev/null; grep -q 'Přehled' "$TMP/body" && grep -q 'Kč' "$TMP/body" && ok "/" "Czech, CZK" || fail "/" "not Czech or not CZK" ;;
    multikomunikacije) page / >/dev/null; grep -q 'Pregled' "$TMP/body" && grep -q '€' "$TMP/body" && ok "/" "Croatian, EUR" || fail "/" "not Croatian or not EUR" ;;
    default) page / >/dev/null; grep -q 'Overview' "$TMP/body" && ok "/" "English" || fail "/" "not English" ;;
  esac

  echo "-- an invoice"
  page /invoices >/dev/null
  INVOICE=$(grep -o 'href="/invoices/[0-9a-f-]\{36\}"' "$TMP/body" | head -1 | cut -d'"' -f2)
  code=$(page "$INVOICE")
  if head -c 4 "$TMP/body" | grep -q '%PDF'; then
    grep -qi '^content-type: application/pdf' "$H" && grep -qi '^cache-control: private, no-store' "$H" \
      && grep -qi '^content-disposition: attachment' "$H" && ok "$INVOICE" "PDF, private, attachment" || fail "$INVOICE" "headers"
  else
    ok "$INVOICE" "no PDF in the development CRM ($code)"
  fi
  code=$(page /invoices/00000000-0000-0000-0000-000000000000)
  [ "$code" = 404 ] && ok "an invoice of nobody" "404" || fail "an invoice of nobody" "$code"

  echo "-- the consents and a change request"
  CONSENTS=$(psql -d "$DEV_DB" -Atc "SELECT concat_ws(',', agree_gdpr, agree_mailing_billing, agree_mailing_outages, agree_mailing_commercial, modified_by) FROM customers WHERE id = $CUSTOMER")
  LABELLED=$(psql -d "$DEV_DB" -Atc "SELECT count(*) FROM customer_labels JOIN labels ON (labels.id = label_id) WHERE customer_id = $CUSTOMER AND labels.name = 'GDPR'")
  page /details >/dev/null
  code=$(c -o /dev/null -w '%{http_code}' --data-urlencode "_csrfToken=$(token)" --data-urlencode agree_mailing_billing=1 \
    --data-urlencode agree_mailing_outages=0 --data-urlencode agree_mailing_commercial=0 "$B/details/consents")
  [ "$(psql -d "$DEV_DB" -Atc "SELECT concat_ws(',', agree_mailing_billing, agree_mailing_outages) FROM customers WHERE id = $CUSTOMER")" = "t,f" ] \
    && ok "/details/consents ($code)" "saved in the CRM" || fail "/details/consents" "$code, not saved"
  IFS=, read -r G B1 O C M <<< "$CONSENTS"
  psql -d "$DEV_DB" -c "UPDATE customers SET agree_gdpr = '$G', agree_mailing_billing = '$B1', agree_mailing_outages = '$O', agree_mailing_commercial = '$C', modified_by = NULLIF('$M', '')::uuid WHERE id = $CUSTOMER" >/dev/null || FAILED=1
  [ "$LABELLED" = 0 ] && psql -d "$DEV_DB" -c "DELETE FROM customer_labels WHERE customer_id = $CUSTOMER AND note = 'form' AND label_id IN (SELECT id FROM labels WHERE name = 'GDPR')" >/dev/null
  dk exec $MAIL wget -q -O - --method=DELETE http://localhost:8025/api/v1/messages >/dev/null 2>&1
  page /details/change >/dev/null
  code=$(c -o /dev/null -w '%{http_code}' --data-urlencode "_csrfToken=$(token)" --data-urlencode company="Nested Change Ltd" \
    --data-urlencode note="A test of the portal." "$B/details/change")
  sleep 1
  dk exec $MAIL wget -q -O - http://localhost:8025/api/v1/messages > "$TMP/mail.json" 2>/dev/null
  grep -q 'contracts@example.invalid' "$TMP/mail.json" && grep -q 'Nested Change Ltd' "$TMP/mail.json" \
    && ok "/details/change ($code)" "mailed with the change" || fail "/details/change" "$code, no mail with the change"

  echo "-- the password"
  page /password >/dev/null
  code=$(c -o "$TMP/body" -w '%{http_code}' --data-urlencode "_csrfToken=$(token)" --data-urlencode current_password=wrong \
    --data-urlencode new_password=$PASSWORD --data-urlencode new_password_again=$PASSWORD "$B/password")
  grep -q 'class="alert error"' "$TMP/body" && ok "a wrong current password" "refused" || fail "a wrong current password" "$code"
  page /password >/dev/null
  code=$(c -o /dev/null -w '%{http_code}' --data-urlencode "_csrfToken=$(token)" --data-urlencode current_password=$PASSWORD \
    --data-urlencode new_password=$PASSWORD --data-urlencode new_password_again=$PASSWORD "$B/password")
  [ "$code" = 302 ] || [ "$code" = 303 ] && ok "the right current password" "changed ($code)" || fail "the right current password" "$code"

  echo "-- branding and old addresses"
  if [ "$PROFILE" != default ]; then
    code=$(c -D "$H" -o /dev/null -w '%{http_code}' "$B/branding/colors.css")
    [ "$code" = 200 ] && grep -qi '^content-security-policy: default-src .none.' "$H" && ok "/branding/colors.css" "200, sandboxed" || fail "/branding/colors.css" "$code"
    page / >/dev/null
    grep -q 'rel="icon" href="/branding/favicon-32.png"' "$TMP/body" && ok "/" "the profile's favicon" || fail "/" "no favicon"
  fi
  for p in "/branding/..%2Fprofile.php" /branding/profile.php; do
    code=$(c -o /dev/null -w '%{http_code}' "$B$p")
    [ "$code" = 404 ] && ok "$p" "404" || fail "$p" "$code"
  done
  for p in "/index.php /" "/menu.php /" "/reports/customer-data.php /details" "/administration/password.php /password"; do
    set -- $p
    loc=$(c -o /dev/null -w '%{http_code} %{redirect_url}' "$B$1")
    [ "$loc" = "301 $B$2" ] && ok "$1" "→ $2" || fail "$1" "$loc"
  done
  c -o /dev/null "$B/vendor/jost/jost-latin.woff2" -w '%{http_code}' | grep -q 200 && ok "the font" "served from webroot/vendor" || fail "the font" "not served"

  echo "-- signing out"
  before=$(page /)
  out=$(c -o /dev/null -w '%{http_code}' --data-urlencode "_csrfToken=$(token)" "$B/sign-out")
  code=$(page /)
  [ "$code" = 302 ] && ok "after signing out" "signed in no more" || fail "after signing out" "/ $before, sign-out $out, / $code"
done

[ $FAILED = 0 ] && echo "== ALL PASSED" || echo "== FAILED"
exit $FAILED
