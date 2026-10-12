<?php
declare(strict_types=1);

namespace App\Crm;

final readonly class Phone
{
    /**
     * @param string $id
     * @param string $phone
     */
    public function __construct(
        public string $id,
        public string $phone,
    ) {
    }

    /**
     * A phone number as the CRM answers it.
     *
     * @param \App\Crm\Fields $fields The phone number.
     * @return self
     */
    public static function fromFields(Fields $fields): self
    {
        return new self($fields->text('id'), $fields->text('phone'));
    }
}
