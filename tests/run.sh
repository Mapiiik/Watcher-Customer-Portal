#!/usr/bin/env bash
# Runs the production image of the portal against the development Watcher CRM, once with the
# default profile and once with every profile in data/*/profile: lint, undefined constants,
# attacks on signing in, every page with all errors shown, sending the customer data form.
# Everything it creates is removed again.
#
# Usage: tests/run.sh
# Needs the Watcher CRM development containers (web server, database and their network) running.
set -u
ROOT=$(cd "$(dirname "$0")/.." && pwd)
DB_CONTAINER=${DEV_DB_CONTAINER:-watcher-crm-database-dev-1}
NETWORK=${DEV_NETWORK:-watcher-crm_default}
# the database the development CRM works with, where its test login and API user go
DEV_DB=${DEV_DB:-watcher_crm}
# the development web server answers only to localhost, so the container carries its port there
CRM_HOST=${DEV_CRM_HOST:-webserver-dev:443}
PORT=${PORT:-18443}
RUN=customer-portal-test
TMP="$ROOT/tests/.tmp"
dk() { MSYS_NO_PATHCONV=1 docker "$@"; }
psql() { dk exec -i "$DB_CONTAINER" psql -U postgres -q -v ON_ERROR_STOP=1 "$@"; }
random() { head -c "$1" /dev/urandom | od -An -tx1 | tr -d ' \n'; }
host_path() { if command -v cygpath >/dev/null; then cygpath -w "$1"; else echo "$1"; fi; }

cd "$ROOT" || exit 1
PW=$(dk inspect "$DB_CONTAINER" --format '{{range .Config.Env}}{{println .}}{{end}}' | sed -n 's/^POSTGRES_PASSWORD=//p')
API_KEY=$(random 24)
SHA=$(printf 'Test-Password-2026' | sha1sum | cut -c1-40)
FAILED=0

cleanup() {
  dk rm -f $RUN >/dev/null 2>&1
  # the API user stays, as what it changed names it, but without a key that works
  psql -d "$DEV_DB" -c "DELETE FROM logins WHERE login = 'zz-portal-test'" \
    -c "UPDATE users SET active = false, api_token = NULL WHERE username = 'zz-portal-test'"
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
  -c "UPDATE users SET active = true, role = 'customer-portal', api_token = '$API_KEY' WHERE username = 'zz-portal-test'"

# the default profile, then each one an operator keeps in data/
PROFILES="default $(ls -d data/*/profile 2>/dev/null | cut -d/ -f2 | tr '\n' ' ')"
for PROFILE in $PROFILES; do
  echo "== profile $PROFILE"
  dk rm -f $RUN >/dev/null 2>&1
  psql -d "$DEV_DB" -c "DELETE FROM logins WHERE login = 'zz-portal-test'" -c "INSERT INTO logins (id, login, password, rights, locked, customer_id, created, modified)
    SELECT uuid_generate_v4(), 'zz-portal-test', '$SHA', 1, false,
      (SELECT customer_id FROM invoices GROUP BY customer_id ORDER BY count(*) DESC LIMIT 1), now(), now()"
  MOUNT=()
  [ "$PROFILE" != default ] && MOUNT=(-v "$(host_path "$ROOT/data/$PROFILE/profile"):/app/profile:ro")
  # the language and currency an operator's deployment sets, the defaults (en_US, EUR) otherwise
  [ -f "data/$PROFILE/test.env" ] && MOUNT+=(--env-file "data/$PROFILE/test.env")
  dk run -d --name $RUN --network "$NETWORK" -p $PORT:443 "${MOUNT[@]}" \
    -e SERVER_NAME=localhost -e OPERATOR_NAME="Test Operator, s.r.o." -e OPERATOR_WEBSITE=https://www.example.net \
    -e PRIVACY_POLICY_URL=https://www.example.net/privacy -e PORTAL_DEBUG=true \
    -e CRM_API_URL=https://localhost:8443/api/customer-portal -e CRM_API_KEY="$API_KEY" -e CRM_API_VERIFY_TLS=false \
    -e SMTP_HOST=127.0.0.1 -e SMTP_PORT=2525 -e SMTP_SECURE=none -e MAIL_FROM=portal@example.net \
    -e CHANGE_REQUESTS_TO=nobody@example.invalid \
    watcher-customer-portal:test >/dev/null || exit 1
  dk exec -d $RUN socat TCP-LISTEN:8443,fork,reuseaddr TCP:$CRM_HOST
  dk cp tests/security-test.php $RUN:/tmp/security-test.php
  dk cp tests/undefined-constants.php $RUN:/tmp/undefined-constants.php
  sleep 3

  echo "-- lint"
  LINT=$(dk exec $RUN sh -c "find /app/src /app/public /app/profiles /app/profile -name '*.php' -exec php -l {} \; 2>/dev/null | grep -v '^No syntax errors'")
  [ -n "$LINT" ] && { echo "$LINT"; FAILED=1; }

  echo "-- constants"
  dk exec $RUN php /tmp/undefined-constants.php 2>&1 | tee "$TMP/constants.txt"
  grep -q '^0 undefined' "$TMP/constants.txt" || FAILED=1

  echo "-- attacks on signing in and on the CRM's answers"
  dk exec -e "ADMIN_DSN=host=$DB_CONTAINER user=postgres password=$PW dbname=$DEV_DB" -e "TEST_IP=192.0.2.$((RANDOM % 250 + 1))" \
    $RUN php -d error_reporting=-1 /tmp/security-test.php | grep -E '^(FAIL|ALL|[0-9]+ FAILED)|Deprecated|Fatal|Warning' \
    | tee "$TMP/security.txt"
  grep -q '^ALL PASSED' "$TMP/security.txt" || FAILED=1

  echo "-- pages"
  B=https://localhost:$PORT
  H="$TMP/http"
  c() { curl -sk "$@"; }
  c -D "$H" -o /dev/null "$B/index.php?sid=attackerchosen123"
  C1=$(grep -i "^set-cookie: PHPSESSID" "$H" | head -1 | sed -E 's/.*PHPSESSID=([^;]+).*/\1/')
  grep -qi "^set-cookie: PHPSESSID=.*secure; HttpOnly; SameSite=Lax" "$H" || { echo "FAIL cookie flags"; FAILED=1; }
  [ "$C1" != attackerchosen123 ] || { echo "FAIL session id from the address accepted"; FAILED=1; }
  c -D "$H" -o /dev/null -H "Cookie: PHPSESSID=$C1" --data-urlencode login_me=yes \
    --data-urlencode login=zz-portal-test --data-urlencode password=Test-Password-2026 "$B/index.php"
  C2=$(grep -i "^set-cookie: PHPSESSID" "$H" | tail -1 | sed -E 's/.*PHPSESSID=([^;]+).*/\1/')
  [ -n "$C2" ] && [ "$C2" != "$C1" ] || { echo "FAIL no new session id after signing in"; FAILED=1; }

  for p in index.php menu.php introduction.php/2026/10/ reports/invoices.php reports/customer-data.php \
           administration/password.php "reports/invoices.php?invoice=00000000-0000-0000-0000-000000000000"; do
    code=$(c -o "$H.body" -w '%{http_code}' -H "Cookie: PHPSESSID=$C2" "$B/$p")
    # the system error is what the portal says when the CRM did not answer
    bad=$(sed -e 's/<[^>]*>//g' "$H.body" | grep -c -i -E "warning|fatal|deprecated|notice|uncaught|System error|Chyba systému|Greška sustava")
    printf "  %-66s %s problems=%s bytes=%s\n" "$p" "$code" "$bad" "$(wc -c < "$H.body")"
    sed -e 's/<[^>]*>//g' "$H.body" | grep -o -i -E "(warning|fatal error|deprecated|notice|uncaught)[^:]*:.{0,170}" | sort -u | head -5 | sed 's/^/      /'
    [ "$code" = 200 ] && [ "$bad" = 0 ] || FAILED=1
  done

  # sending the customer data form saves the consents and compares what was typed with what the CRM
  # holds; both are put back as they were afterwards
  CUSTOMER="(SELECT customer_id FROM logins WHERE login = 'zz-portal-test')"
  CONSENTS=$(psql -d "$DEV_DB" -Atc "SELECT concat_ws(',', agree_gdpr, agree_mailing_billing, agree_mailing_outages, agree_mailing_commercial, modified_by) FROM customers WHERE id = $CUSTOMER")
  LABELLED=$(psql -d "$DEV_DB" -Atc "SELECT count(*) FROM customer_labels JOIN labels ON (labels.id = label_id) WHERE customer_id = $CUSTOMER AND labels.name = 'GDPR'")
  code=$(c -o "$H.body" -w '%{http_code}' -H "Cookie: PHPSESSID=$C2" --data-urlencode agree_gdpr=yes \
    --data-urlencode agree_mailing_billing=yes --data-urlencode company=Changed --data-urlencode 'email[new][email]=' \
    --data-urlencode 'phone[new][phone]=' --data-urlencode 'address[new][street]=Somewhere' "$B/reports/customer-data.php")
  bad=$(sed -e 's/<[^>]*>//g' "$H.body" | grep -c -i -E "warning|fatal|deprecated|notice|uncaught|System error|Chyba systému|Greška sustava")
  printf "  %-66s %s problems=%s\n" "POST reports/customer-data.php" "$code" "$bad"
  [ "$code" = 200 ] && [ "$bad" = 0 ] && grep -q ' OK' "$H.body" || FAILED=1
  IFS=, read -r G B1 O C M <<< "$CONSENTS"
  psql -d "$DEV_DB" -c "UPDATE customers SET agree_gdpr = '$G', agree_mailing_billing = '$B1', agree_mailing_outages = '$O', agree_mailing_commercial = '$C', modified_by = NULLIF('$M', '')::uuid WHERE id = $CUSTOMER" || FAILED=1
  [ "$LABELLED" = 0 ] && psql -d "$DEV_DB" -c "DELETE FROM customer_labels WHERE customer_id = $CUSTOMER AND note = 'form' AND label_id IN (SELECT id FROM labels WHERE name = 'GDPR')"

  # and they show what the CRM answered, not just no error
  expect() {
    c -H "Cookie: PHPSESSID=$C2" "$B/$1" | grep -q "$2" && printf "  %-66s shows %s\n" "$1" "$3" \
      || { printf "FAIL %-61s shows no %s\n" "$1" "$3"; FAILED=1; }
  }
  expect reports/customer-data.php 'name="varsym" type="text" value="[0-9]' "the customer number"
  expect reports/invoices.php 'invoices.php?invoice=[0-9a-f-]\{36\}' "invoices"
  case "$PROFILE" in
    # ICU puts a no-break space between the amount and the currency
    netair) expect menu.php 'Uživatelské údaje' "Czech"; expect reports/invoices.php '[0-9],[0-9][0-9].\{1,3\}Kč' "prices in CZK" ;;
    multikomunikacije) expect menu.php 'Korisnički podaci' "Croatian"; expect reports/invoices.php '[0-9],[0-9][0-9].\{1,3\}€' "prices in EUR" ;;
    default) expect menu.php 'Customer data' "English"; expect reports/invoices.php '€[0-9]' "prices in EUR" ;;
  esac
  # the sign-in page, the one with the footer
  c "$B/index.php" | grep -q 'www.example.net">Test Operator, s.r.o.</a>' && echo "  index.php signed out                                               shows the operator in the footer" \
    || { echo "FAIL index.php signed out shows no operator in the footer"; FAILED=1; }
  if [ "$PROFILE" = default ]; then
    expect reports/customer-data.php 'Test Operator, s.r.o.' "the operator in the consents"
  else
    expect menu.php 'branding.php?file=logo' "the profile's logo"
    expect index.php 'rel="icon" href="/branding.php?file=favicon-32.png" type="image/png" sizes="32x32"' "the favicon"
    expect menu.php 'rel="apple-touch-icon"' "the home screen icon"
    c "$B/index.php" | grep -q 'rel="icon"' && echo "  index.php signed out                                               shows the favicon" \
      || { echo "FAIL index.php signed out shows no favicon"; FAILED=1; }
  fi

  echo "-- branding"
  [ "$(c -o /dev/null -w '%{http_code}' "$B/branding.php?file=../profile.php")" = 404 ] || { echo "FAIL a path outside assets/ is served"; FAILED=1; }
  [ "$(c -o /dev/null -w '%{http_code}' "$B/branding.php?file=profile.php")" = 404 ] || { echo "FAIL a PHP file is served"; FAILED=1; }
  if [ "$PROFILE" != default ]; then
    for f in colors.css favicon-32.png; do
      code=$(c -o /dev/null -w '%{http_code} %{content_type}' "$B/branding.php?file=$f")
      echo "  $f $code"
      [ "${code%% *}" = 200 ] || FAILED=1
    done
  fi
  [ "$(c -o /dev/null -w '%{http_code}' "$B/fonts/jost-latin.woff2")" = 200 ] || { echo "FAIL the font is not served"; FAILED=1; }
  c "$B/style/portal.css" | grep -q "googleapis" && { echo "FAIL the CSS still loads from Google"; FAILED=1; }
done

[ $FAILED = 0 ] && echo "== ALL PASSED" || echo "== FAILED"
exit $FAILED
