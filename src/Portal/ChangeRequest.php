<?php
declare(strict_types=1);

namespace App\Portal;

use App\Crm\Address;
use App\Crm\CustomerDetails;

/**
 * The changes of their details a customer asks for, compared with what the CRM holds. They are
 * not written anywhere: they go by mail to be checked and entered.
 */
final readonly class ChangeRequest
{
    /**
     * @param list<string> $changes Each change in words.
     * @param string $note What the customer adds.
     */
    private function __construct(
        public array $changes,
        public string $note,
    ) {
    }

    /**
     * @param \App\Crm\CustomerDetails $before What the CRM holds.
     * @param array<array-key, mixed> $form The change form as it was sent.
     */
    public static function fromForm(CustomerDetails $before, array $form): self
    {
        $changes = [];

        foreach (CustomerDetails::FIELDS as $field) {
            if (!array_key_exists($field, $form)) {
                continue;
            }
            $was = self::shown($field, $before->value($field));
            $now = self::shown($field, self::text($form[$field]));
            if ($was !== $now) {
                $changes[] = __('{0}: {1} → {2}', Labels::field($field), $was, $now);
            }
        }

        $emails = self::rows($form, 'emails');
        foreach ($before->emails as $email) {
            if (!isset($emails[$email->id])) {
                continue;
            }
            $row = $emails[$email->id];
            $was = self::email($email->email, $email->billing, $email->outages, $email->commercial);
            if (self::text($row['email'] ?? null) === '') {
                $changes[] = __('E-mail removed: {0}', $was);
                continue;
            }
            $now = self::emailRow($row);
            if ($was !== $now) {
                $changes[] = __('E-mail: {0} → {1}', $was, $now);
            }
        }
        if (self::text($emails['new']['email'] ?? null) !== '') {
            $changes[] = __('E-mail added: {0}', self::emailRow($emails['new']));
        }

        $phones = self::rows($form, 'phones');
        foreach ($before->phones as $phone) {
            if (!isset($phones[$phone->id])) {
                continue;
            }
            $now = self::text($phones[$phone->id]['phone'] ?? null);
            if ($now === '') {
                $changes[] = __('Phone removed: {0}', $phone->phone);
            } elseif ($now !== $phone->phone) {
                $changes[] = __('Phone: {0} → {1}', $phone->phone, $now);
            }
        }
        $newPhone = self::text($phones['new']['phone'] ?? null);
        if ($newPhone !== '') {
            $changes[] = __('Phone added: {0}', $newPhone);
        }

        $addresses = self::rows($form, 'addresses');
        foreach ($before->addresses as $address) {
            if (!isset($addresses[$address->id])) {
                continue;
            }
            $row = $addresses[$address->id];
            $type = Labels::addressType($address->type);
            $was = self::address($address->values, $before);
            if (filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $changes[] = __('{0} removed: {1}', $type, $was);
                continue;
            }
            $now = self::address(self::addressValues($row), $before);
            if ($was !== $now) {
                $changes[] = __('{0}: {1} → {2}', $type, $was, $now);
            }
        }
        $new = self::addressValues($addresses['new'] ?? []);
        if ($new['street'] . $new['number'] . $new['city'] !== '') {
            $type = (int)self::text($addresses['new']['type'] ?? null);
            $changes[] = __('{0} added: {1}', Labels::addressType($type), self::address($new, $before));
        }

        return new self($changes, self::text($form['note'] ?? null));
    }

    /**
     * Whether there is anything for the operator to do.
     */
    public function isEmpty(): bool
    {
        return $this->changes === [] && $this->note === '';
    }

    /**
     * A value as the mail shows it.
     */
    private static function shown(string $field, string $value): string
    {
        if ($value === '') {
            return '–';
        }

        return $field === 'invoice_delivery_type' ? Labels::invoiceDelivery($value) : $value;
    }

    /**
     * @param array<string, string> $row
     */
    private static function emailRow(array $row): string
    {
        $flag = static fn(string $key): bool => filter_var($row[$key] ?? false, FILTER_VALIDATE_BOOLEAN);

        return self::email(self::text($row['email'] ?? null), $flag('billing'), $flag('outages'), $flag('commercial'));
    }

    /**
     * An e-mail address and what it is used for, in one line.
     *
     * @param string $email The address.
     * @param bool $billing
     * @param bool $outages
     * @param bool $commercial
     * @return string
     */
    private static function email(string $email, bool $billing, bool $outages, bool $commercial): string
    {
        $uses = Labels::emailUses($billing, $outages, $commercial);

        return $uses === '' ? $email : $email . ' (' . $uses . ')';
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
     * An address in one line: who, street, town and country.
     *
     * @param array<string, string> $values
     */
    private static function address(array $values, CustomerDetails $customer): string
    {
        $address = new Address('', Address::INSTALLATION, $values);
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
