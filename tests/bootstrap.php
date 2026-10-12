<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Log\Log;

/*
 * The tests run with the settings the portal cannot do without, pointing nowhere: the CRM is a
 * fake and mail goes to the test transport. Every other setting is cleared, so that the
 * environment of a development container (its language, its payment codes) is not what the
 * tests run with; a test that needs a setting writes it to Configure.
 */
foreach (
    [
    'CRM_API_VERIFY_TLS', 'MAIL_FROM_NAME', 'OPERATOR_ADDRESS', 'OPERATOR_WEBSITE', 'PAYMENT_ACCOUNT',
    'PAYMENT_CODE', 'PAYMENT_IBAN', 'PORTAL_DEBUG', 'PRIVACY_POLICY_URL', 'SECURITY_SALT',
    'SESSION_IDLE_MINUTES', 'SMTP_PASSWORD', 'SMTP_PORT', 'SMTP_SECURE', 'SMTP_USERNAME',
    'SUPPORT_EMAIL', 'SUPPORT_PHONE', 'TIMEZONE',
    ] as $name
) {
    unset($_SERVER[$name], $_ENV[$name]);
    putenv($name);
}
foreach (
    [
    'OPERATOR_NAME' => 'Test Operator, s.r.o.',
    'CRM_API_URL' => 'https://crm.example.invalid/api/customer-portal',
    'CRM_API_KEY' => 'test-key',
    'SMTP_HOST' => 'localhost',
    'MAIL_FROM' => 'portal@example.invalid',
    'CHANGE_REQUESTS_TO' => 'contracts@example.invalid',
    'APP_DEFAULT_LOCALE' => 'en_US',
    'CURRENCY' => 'EUR',
    ] as $name => $value
) {
    // env() reads $_SERVER before getenv(), so both are set
    $_SERVER[$name] = $_ENV[$name] = $value;
    putenv($name . '=' . $value);
}

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

// the errors the tests cause on purpose are checked through the answers, not printed among the results
Log::drop('debug');
Log::drop('error');

// the translations as the catalogues have them now, not as an earlier run cached them
Cache::clear('_cake_translations_');

session_id('cli');
