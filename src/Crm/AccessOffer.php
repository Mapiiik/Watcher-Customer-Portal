<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * What a link to set a password lets the customer do: set the password of one of their logins,
 * or get a new login where they have none.
 */
final readonly class AccessOffer
{
    /**
     * @param string $email The e-mail the link was sent to.
     * @param string $customerNumber The customer's number.
     * @param string $customerName The customer's name.
     * @param array<string, string> $logins The logins to choose from, by their ids.
     * @param string $newLogin The login made, where there is none to choose.
     * @param int $passwordMinLength The shortest password allowed.
     */
    public function __construct(
        public string $email,
        public string $customerNumber,
        public string $customerName,
        public array $logins,
        public string $newLogin,
        public int $passwordMinLength,
    ) {
    }
}
