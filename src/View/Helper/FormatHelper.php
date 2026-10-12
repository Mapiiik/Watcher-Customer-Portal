<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Crm\Invoice;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\I18n;
use Cake\I18n\Number;
use Cake\View\Helper;
use IntlDateFormatter;

/**
 * Dates and amounts as the language of the portal writes them, and the state of an invoice.
 *
 * @extends \Cake\View\Helper<\App\View\AppView>
 */
class FormatHelper extends Helper
{
    /**
     * A date of the CRM (Y-m-d), or a dash when there is none.
     */
    public function date(string $date): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $date, $parts) !== 1) {
            return $date === '' ? '–' : $date;
        }

        // composed from its parts, so that no time zone moves the day
        return (string)Date::create((int)$parts[1], (int)$parts[2], (int)$parts[3])
            ->i18nFormat(IntlDateFormatter::MEDIUM);
    }

    /**
     * An amount in the operator's currency.
     */
    public function money(float $amount): string
    {
        return Number::currency($amount, (string)Configure::read('Portal.currency'));
    }

    /**
     * The language of the pages, for the lang attribute: cs of cs_CZ.
     */
    public function language(): string
    {
        return strtolower(substr(I18n::getLocale(), 0, 2));
    }

    /**
     * The state of an invoice, as a word and the class of its badge.
     *
     * @param string $today Y-m-d.
     * @return array{0: string, 1: string}
     */
    public function invoiceState(Invoice $invoice, string $today): array
    {
        return match (true) {
            $invoice->isPaid() => [__('Paid'), 'ok'],
            $invoice->isOverdue($today) => [__('Overdue'), 'late'],
            default => [__('Due'), 'due'],
        };
    }
}
