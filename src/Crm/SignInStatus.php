<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * What the CRM said to a login and password.
 */
enum SignInStatus: string
{
    case Granted = 'granted';
    case Denied = 'denied';
    case Locked = 'locked';
    // too many failed attempts, the password was not looked at
    case Blocked = 'blocked';
}
