<?php
declare(strict_types=1);

/*
 * Loads the configuration and sets up what the rest of the portal relies on. The portal has no
 * database: everything it shows comes from Watcher CRM over its API.
 */
require __DIR__ . DIRECTORY_SEPARATOR . 'paths.php';
require CORE_PATH . 'config' . DS . 'bootstrap.php';

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Core\Exception\CakeException;
use Cake\Error\ErrorTrap;
use Cake\Error\ExceptionTrap;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Utility\Security;

require CAKE . 'functions.php';

Configure::config('default', new PhpConfig());
Configure::load('app', 'default', false);

date_default_timezone_set(Configure::read('App.defaultTimezone'));
mb_internal_encoding(Configure::read('App.encoding'));
ini_set('intl.default_locale', Configure::read('App.defaultLocale'));

(new ErrorTrap(Configure::read('Error')))->register();
(new ExceptionTrap(Configure::read('Error')))->register();

Cache::setConfig(Configure::consume('Cache'));
TransportFactory::setConfig(Configure::consume('EmailTransport'));
Mailer::setConfig(Configure::consume('Email'));
Log::setConfig(Configure::consume('Log'));
Security::setSalt(Configure::consume('Security.salt'));

// Absolute links, such as the ones to set a password sent by e-mail, would otherwise be built on
// whatever site a request names in its Host header. Outside debug the portal does not run
// without its own address.
if (!Configure::read('App.fullBaseUrl') && !Configure::read('debug') && env('HTTP_HOST')) {
    throw new CakeException('App.fullBaseUrl is not configured. Set APP_FULL_BASE_URL to the address of the portal.');
}
