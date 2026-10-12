<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * An ip address of the customer with the contract it serves and that contract's code for
 * changing the provider.
 */
final readonly class Connection
{
    /**
     * @param string $ipAddress
     * @param string $contractNumber
     * @param string $verificationCode Empty where the contract has none.
     */
    public function __construct(
        public string $ipAddress,
        public string $contractNumber,
        public string $verificationCode,
    ) {
    }

    /**
     * A connection as the CRM answers it.
     *
     * @param \App\Crm\Fields $fields The connection.
     * @return self
     */
    public static function fromFields(Fields $fields): self
    {
        return new self(
            $fields->text('ip_address'),
            $fields->text('contract_number'),
            $fields->text('subscriber_verification_code'),
        );
    }
}
