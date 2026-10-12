<?php
declare(strict_types=1);

namespace App\Crm;

final readonly class Phone
{
    /**
     * @param string $id
     * @param string $phone
     * @param bool $billing For invoices and reminders.
     * @param bool $outages For outages and faults.
     * @param bool $commercial For commercial messages.
     */
    public function __construct(
        public string $id,
        public string $phone,
        public bool $billing,
        public bool $outages,
        public bool $commercial,
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
        return new self(
            $fields->text('id'),
            $fields->text('phone'),
            $fields->flag('use_for_billing'),
            $fields->flag('use_for_outages'),
            $fields->flag('use_for_commercial'),
        );
    }
}
