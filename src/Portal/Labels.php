<?php
declare(strict_types=1);

namespace App\Portal;

use App\Crm\Address;
use App\Crm\CustomerDetails;

/**
 * The names of the customer's fields and of the values that are numbers in the CRM, for the pages
 * and the mail with the changes a customer asks for.
 */
final class Labels
{
    /**
     * The name of a field of the customer or an address.
     *
     * @param string $field As the CRM names it.
     * @return string
     */
    public static function field(string $field): string
    {
        return match ($field) {
            'company' => __('Company'),
            'title' => __('Title'),
            'first_name' => __('First name'),
            'last_name' => __('Last name'),
            'suffix' => __('Suffix'),
            'date_of_birth' => __('Date of birth'),
            'identity_card_number' => __('ID card number'),
            'identity_number' => __('Company ID'),
            'vat_number' => __('VAT number'),
            'www' => __('Website'),
            'bank_name' => __('Bank'),
            'bank_account' => __('Account number'),
            'bank_code' => __('Bank code'),
            'invoice_delivery_type' => __('Invoice delivery'),
            'street' => __('Street'),
            'number' => __('House number'),
            'city' => __('City'),
            'zip' => __('Postcode'),
            'country_id' => __('Country'),
            default => $field,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function addressTypes(): array
    {
        return [
            Address::INSTALLATION => __('Installation address'),
            Address::BILLING => __('Billing address'),
            Address::DELIVERY => __('Delivery address'),
            Address::PERMANENT => __('Permanent address'),
        ];
    }

    /**
     * The name of a kind of address.
     *
     * @param int $type One of the Address constants.
     * @return string
     */
    public static function addressType(int $type): string
    {
        return self::addressTypes()[$type] ?? __('Address');
    }

    /**
     * @return array<int, string>
     */
    public static function invoiceDeliveries(): array
    {
        return [
            CustomerDetails::INVOICES_NOT_SENT => __('Not sent'),
            CustomerDetails::INVOICES_BY_EMAIL => __('By e-mail'),
        ];
    }

    /**
     * How invoices are sent, in words.
     *
     * @param string $type The CRM's number of it.
     * @return string
     */
    public static function invoiceDelivery(string $type): string
    {
        return is_numeric($type) ? (self::invoiceDeliveries()[(int)$type] ?? $type) : $type;
    }

    /**
     * What an e-mail address is used for, as words.
     */
    public static function emailUses(bool $billing, bool $outages, bool $commercial): string
    {
        return implode(', ', array_keys(array_filter([
            __('billing') => $billing,
            __('outages') => $outages,
            __('commercial messages') => $commercial,
        ])));
    }
}
