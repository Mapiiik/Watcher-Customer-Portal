<?php
declare(strict_types=1);

namespace App\Controller;

use App\Crm\CustomerPortalApi;
use App\Crm\Invoice;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * The customer's invoices, each downloadable as a PDF.
 */
class InvoicesController extends AppController
{
    /**
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @return void
     */
    public function index(CustomerPortalApi $crm): void
    {
        $invoices = $crm->invoices($this->customer()->token);
        $unpaid = array_filter($invoices, static fn(Invoice $invoice): bool => !$invoice->isPaid());

        $this->set([
            'invoices' => $invoices,
            'due' => array_sum(array_map(static fn(Invoice $invoice): float => $invoice->debt, $unpaid)),
            'today' => date('Y-m-d'),
            'title' => __('Invoices'),
        ]);
    }

    /**
     * The PDF of one of the customer's invoices, under the name the profile gives it.
     *
     * @param \App\Crm\CustomerPortalApi $crm Watcher CRM.
     * @param string $id The invoice.
     * @return \Cake\Http\Response
     */
    public function download(CustomerPortalApi $crm, string $id): Response
    {
        $token = $this->customer()->token;
        $invoice = null;
        foreach ($crm->invoices($token) as $candidate) {
            if ($candidate->id === $id) {
                $invoice = $candidate;
            }
        }
        $pdf = $invoice === null ? null : $crm->invoicePdf($token, $id);
        if ($invoice === null || $pdf === null) {
            throw new NotFoundException(__('No such invoice.'));
        }

        // the customer's own document, which nothing on the way may keep
        return $this->getResponse()
            ->withType('pdf')
            ->withStringBody($pdf)
            ->withDownload($this->profile->invoiceFile($invoice->number, $invoice->variableSymbol))
            ->withHeader('Cache-Control', 'private, no-store');
    }

    /**
     * The address of the old portal, reports/invoices.php with ?invoice=<id>.
     *
     * @return \Cake\Http\Response|null
     */
    public function legacy(): ?Response
    {
        $id = $this->getRequest()->getQuery('invoice');

        return $this->redirect(
            is_string($id) && $id !== '' ? ['action' => 'download', $id] : ['action' => 'index'],
            301,
        );
    }
}
