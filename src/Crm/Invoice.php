<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * An invoice of the customer, as the CRM lists it. Dates are Y-m-d.
 */
final readonly class Invoice
{
    /**
     * @param string $id
     * @param string $number
     * @param string $variableSymbol
     * @param string $creationDate
     * @param string $dueDate
     * @param string $paymentDate Empty while unpaid.
     * @param string $text
     * @param float $total
     * @param float $debt What is left to pay.
     */
    public function __construct(
        public string $id,
        public string $number,
        public string $variableSymbol,
        public string $creationDate,
        public string $dueDate,
        public string $paymentDate,
        public string $text,
        public float $total,
        public float $debt,
    ) {
    }

    /**
     * An invoice as the CRM lists it.
     *
     * @param \App\Crm\Fields $fields The invoice.
     * @return self
     */
    public static function fromFields(Fields $fields): self
    {
        return new self(
            $fields->text('id'),
            $fields->text('number'),
            $fields->text('variable_symbol'),
            $fields->text('creation_date'),
            $fields->text('due_date'),
            $fields->text('payment_date'),
            $fields->text('text'),
            $fields->number('total'),
            $fields->number('debt'),
        );
    }

    /**
     * Nothing is left to pay.
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->debt <= 0.0;
    }

    /**
     * Unpaid after its due date.
     *
     * @param string $today Y-m-d.
     */
    public function isOverdue(string $today): bool
    {
        return !$this->isPaid() && $this->dueDate !== '' && $this->dueDate < $today;
    }
}
