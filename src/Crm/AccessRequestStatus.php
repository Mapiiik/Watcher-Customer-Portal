<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * What the CRM said to a customer number and an e-mail asking for a link to set a password.
 *
 * The customer is never told which it was, only whether to wait for an e-mail or to try later.
 */
enum AccessRequestStatus: string
{
    // the two belong together, a link is to be sent
    case Found = 'found';
    // they belong together, but every login of the customer is locked
    case Locked = 'locked';
    case Unknown = 'unknown';
    // too many requests, nothing was looked up
    case Blocked = 'blocked';
}
