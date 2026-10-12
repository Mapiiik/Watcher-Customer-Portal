<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * A contract of the customer with active services, as the CRM answers it.
 */
final readonly class Contract
{
    /**
     * @param string $number
     * @param string $serviceType The kind of service, such as wireless internet.
     * @param string $verificationCode Empty where the contract has none.
     * @param \App\Crm\Address|null $installationAddress Null for a service installed nowhere, such as
     *   television.
     * @param list<string> $services What is billed on it today.
     * @param list<string> $ipAddresses
     * @param list<string> $ipNetworks
     */
    public function __construct(
        public string $number,
        public string $serviceType,
        public string $verificationCode,
        public ?Address $installationAddress,
        public array $services,
        public array $ipAddresses,
        public array $ipNetworks,
    ) {
    }

    /**
     * A contract as the CRM answers it.
     *
     * @param \App\Crm\Fields $fields The contract.
     * @return self
     */
    public static function fromFields(Fields $fields): self
    {
        $address = $fields->object('installation_address');

        return new self(
            $fields->text('number'),
            $fields->text('service_type'),
            $fields->text('subscriber_verification_code'),
            $address->has('id') ? Address::fromFields($address) : null,
            array_values($fields->texts('services')),
            array_values($fields->texts('ip_addresses')),
            array_values($fields->texts('ip_networks')),
        );
    }
}
