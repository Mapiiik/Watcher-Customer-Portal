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
     * @param list<\App\Crm\Connection> $connections
     * @param array<array-key, string> $countries Names by id.
     */
    public function __construct(
        public string $number,
        public string $name,
        public array $values,
        public Consents $consents,
        public array $emails,
        public array $phones,
        public array $addresses,
        public array $connections,
        public array $countries,
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
            array_map(Address::fromFields(...), $customer->list('addresses')),
            array_map(Connection::fromFields(...), $customer->list('ip_addresses')),
            $answer->texts('countries'),
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
     * The name of a country, empty when there is no such one.
     *
     * @param string $id The country.
     * @return string
     */
    public function country(string $id): string
    {
        return $this->countries[$id] ?? '';
    }
}
