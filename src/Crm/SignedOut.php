<?php
declare(strict_types=1);

namespace App\Crm;

use RuntimeException;

/**
 * The CRM no longer takes the customer's token: it expired, or the login was locked meanwhile.
 */
final class SignedOut extends RuntimeException
{
}
