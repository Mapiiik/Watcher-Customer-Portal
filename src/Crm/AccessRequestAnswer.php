<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * The CRM's answer to a customer asking for a link to set a password: the link's token and
 * whom to send it to when there is one.
 */
final readonly class AccessRequestAnswer
{
    /**
     * @param \App\Crm\AccessRequestStatus $status What the CRM said.
     * @param string $token The link's token, when found.
     * @param string $email The e-mail to send to, when found or locked.
     * @param string $customerNumber The customer's number, when found or locked.
     * @param string $customerName The customer's name, when found or locked.
     */
    public function __construct(
        public AccessRequestStatus $status,
        public string $token = '',
        public string $email = '',
        public string $customerNumber = '',
        public string $customerName = '',
    ) {
    }

    /**
     * An answer with nothing to send.
     *
     * @param \App\Crm\AccessRequestStatus $status What the CRM said.
     * @return self
     */
    public static function nothing(AccessRequestStatus $status): self
    {
        return new self($status);
    }
}
