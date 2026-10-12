<?php
declare(strict_types=1);

namespace App\Portal;

use App\Crm\Address;
use App\Crm\CustomerDetails;

/**
 * The changes of their details a customer asks for, compared with what the CRM holds, each as
 * data the CRM could take and in words. For now they go by mail to be checked and entered.
 */
final readonly class ChangeRequest
{
    /**
     * @param list<\App\Portal\Change> $changes
     * @param string $note What the customer adds.
     */
    private function __construct(
        public array $changes,
        public string $note,
    ) {
    }

    /**
     * @param \App\Crm\CustomerDetails $before What the CRM holds.
     * @param array<array-key, mixed> $form The change form as it was sent. What it leaves out
     *   does not change.
     */
    public static function fromForm(CustomerDetails $before, array $form): self
    {
        $changes = [];

        // the customer's own fields, one change of the changed ones
        $was = [];
        $now = [];
        $lines = [];
        foreach (CustomerDetails::FIELDS as $field) {
            if (!array_key_exists($field, $form)) {
                continue;
            }
            $value = self::text($form[$field]);
            if ($value !== $before->value($field)) {
                $was[$field] = $before->value($field);
                $now[$field] = $value;
                $lines[] = __(
                    '{0}: {1} → {2}',
                    Labels::field($field),
                    self::shown($field, $before->value($field)),
                    self::shown($field, $value),
                );
            }
        }
        if ($lines !== []) {
            $changes[] = new Change(Change::CUSTOMER, Change::UPDATE, '', $was, $now, $lines);
        }

        $emails = self::rows($form, 'emails');
        foreach ($before->emails as $email) {
            if (!isset($emails[$email->id])) {
                continue;
            }
            $old = self::contactValues('email', $email->email, $email->billing, $email->outages, $email->commercial);
            $new = self::contactRow('email', $emails[$email->id], $old);
            if ($new['email'] === '') {
                $changes[] = new Change(Change::EMAIL, Change::REMOVE, $email->id, $old, [], [
                    __('E-mail removed: {0}', self::contact('email', $old)),
                ]);
            } elseif ($new !== $old) {
                [$was, $now] = self::diff($old, $new);
                $changes[] = new Change(Change::EMAIL, Change::UPDATE, $email->id, $was, $now, [
                    __('E-mail: {0} → {1}', self::contact('email', $old), self::contact('email', $new)),
                ]);
            }
        }
        $added = self::contactRow('email', $emails['new'] ?? []);
        if ($added['email'] !== '') {
            $changes[] = new Change(Change::EMAIL, Change::ADD, '', [], $added, [
                __('E-mail added: {0}', self::contact('email', $added)),
            ]);
        }

        $phones = self::rows($form, 'phones');
        foreach ($before->phones as $phone) {
            if (!isset($phones[$phone->id])) {
                continue;
            }
            $old = self::contactValues('phone', $phone->phone, $phone->billing, $phone->outages, $phone->commercial);
            $new = self::contactRow('phone', $phones[$phone->id], $old);
            if ($new['phone'] === '') {
                $changes[] = new Change(Change::PHONE, Change::REMOVE, $phone->id, $old, [], [
                    __('Phone removed: {0}', self::contact('phone', $old)),
                ]);
            } elseif ($new !== $old) {
                [$was, $now] = self::diff($old, $new);
                $changes[] = new Change(Change::PHONE, Change::UPDATE, $phone->id, $was, $now, [
                    __('Phone: {0} → {1}', self::contact('phone', $old), self::contact('phone', $new)),
                ]);
            }
        }
        $added = self::contactRow('phone', $phones['new'] ?? []);
        if ($added['phone'] !== '') {
            $changes[] = new Change(Change::PHONE, Change::ADD, '', [], $added, [
                __('Phone added: {0}', self::contact('phone', $added)),
            ]);
        }

        $addresses = self::rows($form, 'addresses');
        foreach ($before->addresses as $address) {
            if (!isset($addresses[$address->id])) {
                continue;
            }
            $row = $addresses[$address->id];
            $type = Labels::addressType($address->type);
            $old = self::addressValues($address->values);
            if (filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $changes[] = new Change(Change::ADDRESS, Change::REMOVE, $address->id, $old, [], [
                    __('{0} removed: {1}', $type, self::address($old, $before)),
                ]);
                continue;
            }
            // what the form leaves out of an address stays as it is
            $new = array_merge($old, array_intersect_key(self::addressValues($row), $row));
            if ($new !== $old) {
                [$was, $now] = self::diff($old, $new);
                // the whole address before and after, and what changes in it at the end
                $fields = array_map(static fn(string $field): string => __(
                    '{0}: {1} → {2}',
                    Labels::field($field),
                    self::addressValue($field, (string)$was[$field], $before),
                    self::addressValue($field, (string)$now[$field], $before),
                ), array_keys($now));
                $changes[] = new Change(Change::ADDRESS, Change::UPDATE, $address->id, $was, $now, [
                    __('{0}: {1} → {2}', $type, self::address($old, $before), self::address($new, $before))
                        . ' (' . implode(', ', $fields) . ')',
                ]);
            }
        }
        $new = self::addressValues($addresses['new'] ?? []);
        if ($new['street'] . $new['number'] . $new['city'] !== '') {
            $type = (int)self::text($addresses['new']['type'] ?? null);
            $changes[] = new Change(Change::ADDRESS, Change::ADD, '', [], ['type' => $type] + $new, [
                __('{0} added: {1}', Labels::addressType($type), self::address($new, $before)),
            ]);
        }

        return new self($changes, self::text($form['note'] ?? null));
    }

    /**
     * Nothing changes and there is nothing to say.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->changes === [] && $this->note === '';
    }

    /**
     * The changes in words, a line each.
     *
     * @return list<string>
     */
    public function lines(): array
    {
        return array_merge([], ...array_map(static fn(Change $change): array => $change->lines, $this->changes));
    }

    /**
     * The request as data, as the CRM could take it.
     *
     * @return array{changes: list<array<string, mixed>>, note: string}
     */
    public function toArray(): array
    {
        return [
            'changes' => array_map(static fn(Change $change): array => $change->toArray(), $this->changes),
            'note' => $this->note,
        ];
    }

    /**
     * A value of the customer as people read it.
     *
     * @param string $field One of CustomerDetails::FIELDS.
     * @param string $value
     * @return string
     */
    private static function shown(string $field, string $value): string
    {
        if ($value === '') {
            return '–';
        }

        return $field === 'invoice_delivery_type' ? Labels::invoiceDelivery($value) : $value;
    }

    /**
     * The fields that differ, as they were and as they are to be.
     *
     * @param array<string, string|int|bool> $old
     * @param array<string, string|int|bool> $new
     * @return array{0: array<string, string|int|bool>, 1: array<string, string|int|bool>}
     */
    private static function diff(array $old, array $new): array
    {
        $changed = array_keys(array_filter(
            $new,
            static fn($value, $field): bool => ($old[$field] ?? null) !== $value,
            ARRAY_FILTER_USE_BOTH,
        ));

        return [array_intersect_key($old, array_flip($changed)), array_intersect_key($new, array_flip($changed))];
    }

    /**
     * An e-mail or a phone row of the form as the CRM names its fields. A use the row leaves out
     * stays as it was.
     *
     * @param string $key The field of the contact itself, email or phone.
     * @param array<string, string> $row
     * @param array<string, string|bool> $was The contact as it is, none for a new one.
     * @return array<string, string|bool>
     */
    private static function contactRow(string $key, array $row, array $was = []): array
    {
        $flag = static fn(string $use): bool => array_key_exists($use, $row)
            ? filter_var($row[$use], FILTER_VALIDATE_BOOLEAN)
            : (bool)($was['use_for_' . $use] ?? false);

        return self::contactValues(
            $key,
            self::text($row[$key] ?? null),
            $flag('billing'),
            $flag('outages'),
            $flag('commercial'),
        );
    }

    /**
     * @param string $key The field of the contact itself, email or phone.
     * @return array<string, string|bool>
     */
    private static function contactValues(
        string $key,
        string $value,
        bool $billing,
        bool $outages,
        bool $commercial,
    ): array {
        return [
            $key => $value,
            'use_for_billing' => $billing,
            'use_for_outages' => $outages,
            'use_for_commercial' => $commercial,
        ];
    }

    /**
     * An e-mail address or a phone number and what it is used for, in one line.
     *
     * @param string $key The field of the contact itself, email or phone.
     * @param array<string, string|bool> $values
     * @return string
     */
    private static function contact(string $key, array $values): string
    {
        $uses = Labels::uses(
            (bool)$values['use_for_billing'],
            (bool)$values['use_for_outages'],
            (bool)$values['use_for_commercial'],
        );

        return $uses === '' ? (string)$values[$key] : $values[$key] . ' (' . $uses . ')';
    }

    /**
     * @param array<string, string> $row
     * @return array<string, string>
     */
    private static function addressValues(array $row): array
    {
        $values = [];
        foreach (Address::FIELDS as $field) {
            $values[$field] = self::text($row[$field] ?? null);
        }

        return $values;
    }

    /**
     * A field of an address as people read it: the name of the country, a dash for nothing.
     *
     * @param string $field One of Address::FIELDS.
     * @param string $value
     * @param \App\Crm\CustomerDetails $customer Who knows the countries.
     * @return string
     */
    private static function addressValue(string $field, string $value, CustomerDetails $customer): string
    {
        if ($field === 'country_id' && $value !== '') {
            $value = $customer->country($value);
        }

        return $value === '' ? '–' : $value;
    }

    /**
     * An address in one line: who, street, town and country.
     *
     * @param array<string, string|int|bool> $values
     */
    private static function address(array $values, CustomerDetails $customer): string
    {
        $address = new Address('', Address::INSTALLATION, array_map('strval', $values));
        $parts = array_filter(
            [$address->name(), $address->street(), $address->town(), $customer->country($address->value('country_id'))],
            static fn(string $part): bool => $part !== '',
        );

        return $parts === [] ? '–' : implode(', ', $parts);
    }

    /**
     * The rows of a list in the form, such as emails[<id>][email], each a set of texts.
     *
     * @param array<array-key, mixed> $form
     * @return array<string, array<string, string>>
     */
    private static function rows(array $form, string $name): array
    {
        $rows = [];
        foreach (is_array($form[$name] ?? null) ? $form[$name] : [] as $key => $row) {
            if (!is_array($row)) {
                continue;
            }
            $fields = [];
            foreach ($row as $field => $value) {
                $fields[(string)$field] = self::text($value);
            }
            $rows[(string)$key] = $fields;
        }

        return $rows;
    }

    /**
     * A form value as trimmed text, empty when it is none.
     *
     * @param mixed $value The value.
     * @return string
     */
    private static function text(mixed $value): string
    {
        return is_scalar($value) ? trim((string)$value) : '';
    }
}
