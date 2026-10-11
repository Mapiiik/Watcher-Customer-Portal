<?php
/**
 * What every page of the portal starts with: its settings from the environment, the operator's
 * profile and language, the session settings and the code the pages use.
 *
 * The pages are the old procedural portal. They read their settings from the constants defined
 * here, and `$profile`, `$address_types` and `$invoice_delivery_type_options` from the global scope.
 */

define('SRC_DIR', __DIR__);
define('ROOT_DIR', dirname(__DIR__));

require ROOT_DIR . '/vendor/autoload.php';

/**
 * A setting from the environment. Without a default, a missing setting stops the page.
 *
 * @param string $name
 * @param string|null $default
 * @return string
 */
function portalSetting($name, $default = null)
{
    $value = getenv($name);
    if ($value !== false && trim($value) !== '') {
        return trim($value);
    }
    if ($default === null) {
        error_log('The customer portal is not configured: ' . $name . ' is not set.');
        http_response_code(500);
        exit;
    }

    return $default;
}

/**
 * @param string $name
 * @param bool $default
 * @return bool
 */
function portalFlag($name, $default)
{
    return filter_var(portalSetting($name, $default ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);
}

define('PREFIX', '');
date_default_timezone_set(portalSetting('TIMEZONE', 'Europe/Prague'));
define('DEBUG', portalFlag('PORTAL_DEBUG', false));

// the operator the portal is for
define('OPERATOR_NAME', portalSetting('OPERATOR_NAME'));
define('OPERATOR_WEBSITE', portalSetting('OPERATOR_WEBSITE', ''));
define('PRIVACY_POLICY_URL', portalSetting('PRIVACY_POLICY_URL', ''));

// the operator's look, language and texts: the profile the deployment mounts at /app/profile,
// with what it leaves out taken from profiles/default
define('DEFAULT_PROFILE_DIR', ROOT_DIR . '/profiles/default');
define('PROFILE_DIR', is_file(ROOT_DIR . '/profile/profile.php') ? ROOT_DIR . '/profile' : DEFAULT_PROFILE_DIR);
$profile = require DEFAULT_PROFILE_DIR . '/profile.php';
if (PROFILE_DIR !== DEFAULT_PROFILE_DIR) {
    $profile = array_replace($profile, require PROFILE_DIR . '/profile.php');
}

define('LANGUAGE', $profile['language']);
define('HTML_PAGE_NAME', $profile['page_name']);

// Watcher CRM, which the portal asks about the customers signed in to it
define('CRM_API_URL', rtrim(portalSetting('CRM_API_URL'), '/'));
define('CRM_API_KEY', portalSetting('CRM_API_KEY'));
define('CRM_API_VERIFY_TLS', portalFlag('CRM_API_VERIFY_TLS', true));

// the mail with the changes a customer asks for
define('SMTP_HOST', portalSetting('SMTP_HOST'));
define('SMTP_PORT', (int)portalSetting('SMTP_PORT', '587'));
define('SMTP_SECURE', portalSetting('SMTP_SECURE', 'tls') === 'none' ? '' : portalSetting('SMTP_SECURE', 'tls'));
define('SMTP_USERNAME', portalSetting('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', portalSetting('SMTP_PASSWORD', ''));
define('SMTP_AUTH', SMTP_USERNAME !== '');
define('MAIL_FROM', portalSetting('MAIL_FROM'));
define('MAIL_FROM_NAME', portalSetting('MAIL_FROM_NAME', HTML_PAGE_NAME));
define('CHANGE_REQUESTS_TO', portalSetting('CHANGE_REQUESTS_TO'));

require SRC_DIR . '/lang/' . basename(LANGUAGE) . '.php';

$invoice_delivery_type_options = [
    0 => INVOICE_DELIVERY_NONE,
    1 => INVOICE_DELIVERY_EMAIL,
];
$address_types = [
    0 => INSTALLATION_ADDRESS,
    1 => BILLING_ADDRESS,
    2 => DELIVERY_ADDRESS,
    3 => PERMANENT_ADDRESS,
];

// the row under the pointer
define('COLOR_POINTED', '#F0F0C1');

// session cookies only over HTTPS, out of reach of scripts, and only with identifiers the server issued itself
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
// kept in a volume of their own, so that an update of the container signs nobody out
if (is_dir(ROOT_DIR . '/sessions') && is_writable(ROOT_DIR . '/sessions')) {
    session_save_path(ROOT_DIR . '/sessions');
}

require SRC_DIR . '/access.class.php';
require SRC_DIR . '/crm.class.php';
require SRC_DIR . '/common.func.php';
require SRC_DIR . '/message.func.php';
require SRC_DIR . '/table.func.php';

/**
 * A part of the profile, from the mounted profile when it has one, otherwise the default.
 *
 * @param string $name consent or introduction
 * @return string
 */
function profilePart($name)
{
    $file = PROFILE_DIR . '/' . $name . '.php';

    return is_file($file) ? $file : DEFAULT_PROFILE_DIR . '/' . $name . '.php';
}
