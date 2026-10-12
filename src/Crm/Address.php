<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * An address of the customer: where the service is installed, where invoices go, and so on.
 */
final readonly class Address
{
    public const INSTALLATION = 0;
    public const BILLING = 1;
    public const DELIVERY = 2;
    public const PERMANENT = 3;

    /**
     * The types in the order the CRM looks for an invoice address in, the delivery address last,
     * which it never takes for one.
     */
    public const ORDER = [self::BILLING, self::PERMANENT, self::INSTALLATION, self::DELIVERY];

    /**
     * The fields a customer may ask to change, as the form and the CRM name them.
     */
    public const FIELDS = [
        'company', 'title', 'first_name', 'last_name', 'suffix', 'street', 'number', 'city', 'zip', 'country_id',
    ];

    /**
     * @param array<string, string> $values The fields of FIELDS.
     */
    public function __construct(
        public string $id,
        public int $type,
        public array $values,
    ) {
    }

    /**
     * An address as the CRM answers it.
     *
     * @param \App\Crm\Fields $fields The address.
     * @return self
     */
    public static function fromFields(Fields $fields): self
    {
        $values = [];
        foreach (self::FIELDS as $field) {
            $values[$field] = $fields->text($field);
        }

        return new self($fields->text('id'), $fields->int('type'), $values);
    }

    /**
     * One of FIELDS, empty when the address has none.
     *
     * @param string $field The field.
     * @return string
     */
    public function value(string $field): string
    {
        return $this->values[$field] ?? '';
    }

    /**
     * The name of the person or company at the address, when it has one of its own.
     */
    public function name(): string
    {
        $person = implode(' ', array_filter([
            $this->value('title'),
            $this->value('first_name'),
            $this->value('last_name'),
            $this->value('suffix'),
        ], static fn(string $part): bool => $part !== ''));

        $parts = array_filter([$this->value('company'), $person], static fn(string $part): bool => $part !== '');

        return implode(', ', $parts);
    }

    /**
     * The street and number, a number alone when the place has no streets.
     */
    public function street(): string
    {
        return trim($this->value('street') . ' ' . $this->value('number'));
    }

    /**
     * The postcode and the town.
     *
     * @return string
     */
    public function town(): string
    {
        return trim($this->value('zip') . ' ' . $this->value('city'));
    }
}
