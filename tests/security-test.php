<?php
// Attacks on signing in and the CRM's answers, run in the portal's container against the
// development CRM. Usage: php security-test.php
require '/app/src/bootstrap.php';

$_SERVER['REMOTE_ADDR'] = getenv('TEST_IP');
ob_start();
session_start();

$failures = 0;
function check($label, $ok) {
    global $failures;
    if (!$ok) $failures++;
    echo ($ok ? 'ok   ' : 'FAIL ') . $label . PHP_EOL;
}

// what the CRM stored is read by an administrator, the portal sees none of it
$admin = pg_connect(getenv('ADMIN_DSN'));
$row = function () use ($admin) {
    return pg_fetch_assoc(pg_query($admin, "SELECT locked, password, customer_id, last_granted, last_granted_ip, last_denied FROM logins WHERE login = 'zz-portal-test'"));
};
$before = $row();
$crm = new Crm();

foreach ([
    "' OR '1'='1' --",
    "zz-portal-test' --",
    "x'; UPDATE logins SET locked = true WHERE login = 'zz-portal-test'; --",
    "' UNION SELECT id, customer_id, '" . sha1('x') . "', 3, false FROM logins LIMIT 1 --",
] as $payload) {
    $s = new Session();
    check('injection rejected: ' . substr($payload, 0, 40), $s->login($crm, $payload, 'x') === A_DENIED);
}
check('stacked UPDATE did not run', $row()['locked'] === $before['locked']);

$s = new Session();
check('wrong password denied', $s->login($crm, 'zz-portal-test', 'wrong') === A_DENIED);
check('denial recorded', $row()['last_denied'] !== $before['last_denied']);
$s = new Session();
check('the stored SHA-1 is no password', $s->login($crm, 'zz-portal-test', $before['password']) === A_DENIED);
$s = new Session();
check('an empty password is not asked about', $s->login($crm, 'zz-portal-test', '') === A_DENIED);

$idBefore = session_id();
$s = new Session();
check('right password granted', $s->login($crm, 'zz-portal-test', 'Test-Password-2026') === A_GRANTED);
check('session id regenerated', session_id() !== $idBefore);
check('grant recorded with address', $row()['last_granted_ip'] === getenv('TEST_IP'));
check('the SHA-1 is replaced with Argon2id', strpos($row()['password'], '$argon2id$') === 0);
check('signed in as the customer of the login', $s->customer_id === $before['customer_id'] && $s->isLogged());

check('setPassword works', $s->setPassword($crm, 'Test-Password-2026'));
check('the new password is Argon2id', strpos($row()['password'], '$argon2id$') === 0);

// every answer is limited to the customer signed in
$answer = $crm->customer($s->token);
check('the customer is answered', is_array($answer) && $answer['customer']['id'] === $s->customer_id);
check('a changed token is refused', $crm->customer($s->token . 'x') === false && $crm->signedOut());
check('without a token nothing is answered', $crm->customer(null) === false && $crm->signedOut());
$invoices = $crm->invoices($s->token);
check('the invoices are listed', is_array($invoices) && count($invoices['invoices']) > 0);
$foreign = pg_fetch_result(pg_query_params($admin, 'SELECT id FROM invoices WHERE customer_id <> $1 LIMIT 1', array($s->customer_id)), 0, 0);
check("another customer's invoice is not found", $crm->invoicePdf($s->token, $foreign) === false && $crm->status == 404);

// the consents are saved, and put back as they were
$was = $answer['customer'];
$label = "SELECT count(*) FROM customer_labels JOIN labels ON (labels.id = label_id) WHERE customer_id = $1 AND labels.name = 'GDPR'";
$labelled = pg_fetch_result(pg_query_params($admin, $label, array($s->customer_id)), 0, 0);
$saved = $crm->consents($s->token, array('agree_gdpr' => 1, 'agree_mailing_billing' => 1, 'agree_mailing_outages' => 0, 'agree_mailing_commercial' => 0));
$now = $crm->customer($s->token)['customer'];
check('the consents are saved', $saved !== false && $now['agree_mailing_billing'] === true && $now['agree_mailing_outages'] === false);
check('the customer is labelled once', pg_fetch_result(pg_query_params($admin, $label, array($s->customer_id)), 0, 0) == 1);
$crm->consents($s->token, array(
    'agree_gdpr' => (int)$was['agree_gdpr'],
    'agree_mailing_billing' => (int)$was['agree_mailing_billing'],
    'agree_mailing_outages' => (int)$was['agree_mailing_outages'],
    'agree_mailing_commercial' => (int)$was['agree_mailing_commercial'],
));
if ($labelled == 0)
{
    pg_query_params($admin, "DELETE FROM customer_labels WHERE customer_id = $1 AND note = 'form' AND label_id IN (SELECT id FROM labels WHERE name = 'GDPR')", array($s->customer_id));
}

// mail without a server fails rather than breaks the page
check('sendMessage without a server returns false', @sendMessage('test', 'nobody@example.invalid', 'Test – portal') === false);

ob_end_flush();
echo $failures === 0 ? "ALL PASSED\n" : "$failures FAILED\n";
exit($failures === 0 ? 0 : 1);
