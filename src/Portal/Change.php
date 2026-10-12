<?php
declare(strict_types=1);

namespace App\Portal;

/**
 * One change a customer asks for, as the CRM names what it changes: the record, whether it is
 * updated, added or removed, and its fields before and after. People read it in its lines, and
 * the CRM can take it as it is.
 */
final readonly class Change
{
    public const CUSTOMER = 'customer';
    public const EMAIL = 'email';
    public const PHONE = 'phone';
    public const ADDRESS = 'address';

    public const UPDATE = 'update';
    public const ADD = 'add';
    public const REMOVE = 'remove';

    /**
     * @param string $record One of CUSTOMER, EMAIL, PHONE, ADDRESS.
     * @param string $action One of UPDATE, ADD, REMOVE.
     * @param string $id The record's id in the CRM, empty for the customer and for what is added.
     * @param array<string, string|int|bool> $before The fields as they are, by the CRM's names: those
     *   that change, or all of a record that is removed.
     * @param array<string, string|int|bool> $after The fields as asked for: those that change, or all
     *   of a record that is added.
     * @param list<string> $lines The change in words.
     */
    public function __construct(
        public string $record,
        public string $action,
        public string $id,
        public array $before,
        public array $after,
        public array $lines,
    ) {
    }

    /**
     * The change as data, without its words.
     *
     * @return array{record: string, action: string, id: string, before: array<string, string|int|bool>, after: array<string, string|int|bool>}
     */
    public function toArray(): array
    {
        return [
            'record' => $this->record,
            'action' => $this->action,
            'id' => $this->id,
            'before' => $this->before,
            'after' => $this->after,
        ];
    }
}
