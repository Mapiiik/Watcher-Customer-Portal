<?php
declare(strict_types=1);

namespace App\Controller;

use App\Crm\CustomerPortalApi;
use App\Crm\Invoice;
use App\Portal\PaymentCodes;

/**
 * The first page after signing in: what is left to pay with the codes to pay it by, the latest
 * invoices, the connections and where to get help.
 */
class OverviewController extends AppController
{
    /**
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @return void
     */
    public function index(CustomerPortalApi $crm): void
    {
        $token = $this->customer()->token;
        $details = $crm->customer($token);
        $invoices = $crm->invoices($token);
        $unpaid = array_values(array_filter($invoices, static fn(Invoice $invoice): bool => !$invoice->isPaid()));
        $due = array_sum(array_map(static fn(Invoice $invoice): float => $invoice->debt, $unpaid));
        $paymentCodes = PaymentCodes::fromConfig();
        $codes = [];
        foreach ($unpaid as $invoice) {
            $code = $paymentCodes->image($invoice);
            if ($code !== null) {
                $codes[$invoice->id] = $code;
            }
        }

        $this->set([
            'details' => $details,
            'unpaid' => $unpaid,
            'due' => $due,
            'codes' => $codes,
            'latest' => array_slice($invoices, 0, 3),
            'today' => date('Y-m-d'),
            'title' => __('Overview'),
        ]);
    }
}
