<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * The signed-in customer as the CRM holds them, with the countries an address may be in.
 */
final readonly class CustomerDetails
{
    /**
     * The fields of the customer a change may be asked for, as the form and the CRM name them.
     */
    public const FIELDS = [
        'company', 'title', 'first_name', 'last_name', 'suffix', 'date_of_birth', 'identity_card_number',
        'identity_number', 'vat_number', 'www', 'bank_name', 'bank_account', 'bank_code', 'invoice_delivery_type',
    ];

    /**
     * Invoices are not sent.
     */
    public const INVOICES_NOT_SENT = 0;

    /**
     * Invoices are sent by e-mail.
     */
    public const INVOICES_BY_EMAIL = 1;

    /**
     * @param array<string, string> $values The fields of FIELDS; the date of birth is Y-m-d.
     * @param list<\App\Crm\Email> $emails
     * @param list<\App\Crm\Phone> $phones
     * @param list<\App\Crm\Address> $addresses
     * @param list<\App\Crm\Contract> $contracts With active services.
     * @param array<array-key, string> $countries Names by id.
     * @param string $id The customer's id in the CRM.
     */
    public function __construct(
        public string $number,
        public string $name,
        public array $values,
        public Consents $consents,
        public array $emails,
        public array $phones,
        public array $addresses,
        public array $contracts,
        public array $countries,
        public string $id = '',
    ) {
    }

    /**
     * The answer of the CRM's customer question.
     */
    public static function fromAnswer(Fields $answer): self
    {
        $customer = $answer->object('customer');
        $values = [];
        foreach (self::FIELDS as $field) {
            $values[$field] = $customer->text($field);
        }

        return new self(
            $customer->text('number'),
            $customer->text('name'),
            $values,
            new Consents(
                $customer->flag('agree_mailing_billing'),
                $customer->flag('agree_mailing_outages'),
                $customer->flag('agree_mailing_commercial'),
            ),
            array_map(Email::fromFields(...), $customer->list('emails')),
            array_map(Phone::fromFields(...), $customer->list('phones')),
            self::inOrder(array_map(Address::fromFields(...), $customer->list('addresses'))),
            array_map(Contract::fromFields(...), $customer->list('contracts')),
            $answer->texts('countries'),
            $customer->text('id'),
        );
    }

    /**
     * One of FIELDS, empty when the customer has none.
     *
     * @param string $field The field.
     * @return string
     */
    public function value(string $field): string
    {
        return $this->values[$field] ?? '';
    }

    /**
     * The person: title, first and last name and suffix, empty for a company without one.
     *
     * @return string
     */
    public function personName(): string
    {
        $parts = [$this->value('title'), $this->value('first_name'), $this->value('last_name'), $this->value('suffix')];

        return implode(' ', array_filter($parts, static fn(string $part): bool => $part !== ''));
    }

    /**
     * Whom a page greets: the person, or the company when there is no person.
     *
     * @return string
     */
    public function greetingName(): string
    {
        foreach ([$this->personName(), $this->value('company'), $this->name] as $name) {
            if ($name !== '') {
                return $name;
            }
        }

        return '';
    }

    /**
     * The address of a type, the first one when there are more.
     */
    public function address(int $type): ?Address
    {
        foreach ($this->addresses as $address) {
            if ($address->type === $type) {
                return $address;
            }
        }

        return null;
    }

    /**
     * The addresses of some types, in the order of the addresses.
     *
     * @param int ...$types Address::INSTALLATION and the others.
     * @return list<\App\Crm\Address>
     */
    public function addressesOf(int ...$types): array
    {
        return array_values(array_filter(
            $this->addresses,
            static fn(Address $address): bool => in_array($address->type, $types, true),
        ));
    }

    /**
     * The numbers of the contracts installed at an address.
     *
     * @param \App\Crm\Address $address One of the customer's addresses.
     * @return list<string>
     */
    public function contractsAt(Address $address): array
    {
        $numbers = [];
        foreach ($this->contracts as $contract) {
            if ($contract->installationAddress?->id === $address->id && $address->id !== '') {
                $numbers[] = $contract->number;
            }
        }

        return $numbers;
    }

    /**
     * The name of a country, empty when there is no such one.
     *
     * @param string $id The country.
     * @return string
     */
    public function country(string $id): string
    {
        return $this->countries[$id] ?? '';
    }

    /**
     * Addresses in the order of their types, as Address::ORDER has it, each type as the CRM
     * listed it.
     *
     * @param list<\App\Crm\Address> $addresses
     * @return list<\App\Crm\Address>
     */
    private static function inOrder(array $addresses): array
    {
        // the sort is stable, so the addresses of one type keep their order
        $rank = array_flip(Address::ORDER);
        $of = static fn(Address $address): int => $rank[$address->type] ?? PHP_INT_MAX;
        usort($addresses, static fn(Address $a, Address $b): int => $of($a) <=> $of($b));

        return $addresses;
    }
}
