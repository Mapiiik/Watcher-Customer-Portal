<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * An e-mail address of the customer and the messages it is used for.
 */
final readonly class Email
{
    /**
     * @param string $id
     * @param string $email
     * @param bool $billing For invoices and reminders.
     * @param bool $outages For outages and faults.
     * @param bool $commercial For commercial messages.
     */
    public function __construct(
        public string $id,
        public string $email,
        public bool $billing,
        public bool $outages,
        public bool $commercial,
    ) {
    }

    /**
     * An e-mail address as the CRM answers it.
     *
     * @param \App\Crm\Fields $fields The address.
     * @return self
     */
    public static function fromFields(Fields $fields): self
    {
        return new self(
            $fields->text('id'),
            $fields->text('email'),
            $fields->flag('use_for_billing'),
            $fields->flag('use_for_outages'),
            $fields->flag('use_for_commercial'),
        );
    }
}
