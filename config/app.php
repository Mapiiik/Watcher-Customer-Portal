<?php
declare(strict_types=1);

use App\Error\PortalExceptionRenderer;
use Cake\Cache\Engine\FileEngine;
use Cake\Log\Engine\ConsoleLog;
use Cake\Mailer\Transport\SmtpTransport;
use function Cake\Core\env;

/*
 * The portal is set up from its environment, as .env.example describes it, and from the
 * operator's profile. Nothing here belongs to an operator: these are defaults.
 */
$setting = static function (string $name, string $default = ''): string {
    $value = env($name);

    return is_string($value) && trim($value) !== '' ? trim($value) : $default;
};
$flag = static fn(string $name, bool $default): bool => filter_var(
    $setting($name, $default ? 'true' : 'false'),
    FILTER_VALIDATE_BOOLEAN,
);
$list = static fn(string $name): array => array_values(array_filter(
    array_map(trim(...), explode(',', $setting($name))),
    static fn(string $item): bool => $item !== '',
));
$secure = $setting('SMTP_SECURE', 'tls');
$sessions = ROOT . DS . 'sessions';

return [
    'debug' => $flag('PORTAL_DEBUG', false),

    'App' => [
        'namespace' => 'App',
        'encoding' => 'UTF-8',
        // the language and formats of the pages, as in Watcher CRM: texts are English in the code
        // and translated from resources/locales/<locale>/default.po
        'defaultLocale' => $setting('APP_DEFAULT_LOCALE', 'en_US'),
        'defaultTimezone' => $setting('TIMEZONE', 'Europe/Prague'),
        'base' => false,
        'dir' => 'src',
        'webroot' => 'webroot',
        'wwwRoot' => WWW_ROOT,
        'fullBaseUrl' => false,
        'imageBaseUrl' => 'img/',
        'cssBaseUrl' => 'css/',
        'jsBaseUrl' => 'js/',
        'paths' => [
            'plugins' => [ROOT . DS . 'plugins' . DS],
            'templates' => [ROOT . DS . 'templates' . DS],
            'locales' => [RESOURCES . 'locales' . DS],
        ],
    ],

    // the portal keeps no secrets of its own, so its salt is derived from its key to the CRM
    'Security' => [
        'salt' => $setting('SECURITY_SALT', hash('sha256', 'watcher-customer-portal|' . $setting('CRM_API_KEY'))),
    ],

    'Asset' => [],

    'Cache' => [
        'default' => [
            'className' => FileEngine::class,
            'path' => CACHE,
        ],
        '_cake_translations_' => [
            'className' => FileEngine::class,
            'prefix' => 'portal_translations_',
            'path' => CACHE . 'persistent' . DS,
            'serialize' => true,
            'duration' => '+1 years',
        ],
    ],

    'Error' => [
        'errorLevel' => E_ALL,
        'exceptionRenderer' => PortalExceptionRenderer::class,
        'skipLog' => [],
        'log' => true,
        'trace' => false,
        'ignoredDeprecationPaths' => [],
    ],

    'Debugger' => [
        'editor' => 'phpstorm',
    ],

    // the mail with the changes a customer asks for
    'EmailTransport' => [
        'default' => [
            'className' => SmtpTransport::class,
            'host' => ($secure === 'ssl' ? 'ssl://' : '') . $setting('SMTP_HOST', 'localhost'),
            'port' => (int)$setting('SMTP_PORT', '587'),
            'username' => $setting('SMTP_USERNAME') ?: null,
            'password' => $setting('SMTP_PASSWORD') ?: null,
            'tls' => $secure === 'tls',
            'timeout' => 30,
            'client' => null,
        ],
    ],
    'Email' => [
        'default' => [
            'transport' => 'default',
            'from' => [
                $setting('MAIL_FROM', 'portal@localhost')
                    => $setting('MAIL_FROM_NAME', $setting('OPERATOR_NAME', 'Customer portal')),
            ],
            'charset' => 'utf-8',
            'headerCharset' => 'utf-8',
        ],
    ],

    'Datasources' => [],

    // into the container's log
    'Log' => [
        'debug' => [
            'className' => ConsoleLog::class,
            'stream' => 'php://stderr',
            'levels' => ['notice', 'info', 'debug'],
        ],
        'error' => [
            'className' => ConsoleLog::class,
            'stream' => 'php://stderr',
            'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
        ],
    ],

    // the session cookie only over HTTPS, out of reach of scripts, and only with identifiers the
    // server issued itself; the __Host- prefix keeps other sites of the domain from setting one.
    // Kept in a volume of its own, so that an update of the container signs nobody out.
    'Session' => [
        'defaults' => 'php',
        'cookie' => '__Host-portal',
        'cookiePath' => '/',
        'timeout' => max(1, (int)$setting('SESSION_IDLE_MINUTES', '30')),
        'ini' => array_filter([
            'session.cookie_secure' => true,
            'session.cookie_httponly' => true,
            'session.cookie_samesite' => 'Lax',
            'session.use_strict_mode' => true,
            'session.save_path' => is_dir($sessions) && is_writable($sessions) ? $sessions : null,
        ], static fn(mixed $value): bool => $value !== null),
    ],

    'Portal' => [
        // the operator the portal is for
        'operator' => [
            'name' => $setting('OPERATOR_NAME'),
            'website' => $setting('OPERATOR_WEBSITE'),
            'address' => $setting('OPERATOR_ADDRESS'),
            'privacyPolicyUrl' => $setting('PRIVACY_POLICY_URL'),
            'supportEmail' => $setting('SUPPORT_EMAIL'),
            'supportPhone' => $setting('SUPPORT_PHONE'),
            'paymentAccount' => $setting('PAYMENT_ACCOUNT'),
        ],
        'currency' => strtoupper($setting('CURRENCY', 'EUR')),
        // the codes a bank's app scans to pay an invoice, see App\Portal\PaymentCodes
        'payment' => [
            'code' => strtolower($setting('PAYMENT_CODE')),
            'iban' => strtoupper(str_replace(' ', '', $setting('PAYMENT_IBAN'))),
        ],
        // Watcher CRM, which the portal asks about the customers signed in to it
        'crm' => [
            'url' => rtrim($setting('CRM_API_URL'), '/'),
            'key' => $setting('CRM_API_KEY'),
            'verifyTls' => $flag('CRM_API_VERIFY_TLS', true),
        ],
        'changeRequestsTo' => $list('CHANGE_REQUESTS_TO'),
        // the operator's look and texts: the profile the deployment mounts at /app/profile, with
        // what it leaves out taken from profiles/default
        'profile' => is_file(ROOT . DS . 'profile' . DS . 'profile.php')
            ? ROOT . DS . 'profile'
            : ROOT . DS . 'profiles' . DS . 'default',
        'defaultProfile' => ROOT . DS . 'profiles' . DS . 'default',
        // the settings the portal cannot do without
        'required' => ['OPERATOR_NAME', 'CRM_API_URL', 'CRM_API_KEY', 'SMTP_HOST', 'MAIL_FROM', 'CHANGE_REQUESTS_TO'],
    ],
];
