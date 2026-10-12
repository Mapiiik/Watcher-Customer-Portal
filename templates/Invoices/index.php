<?php
/**
 * @var \App\View\AppView $this
 * @var list<\App\Crm\Invoice> $invoices
 * @var float $due
 * @var string $today
 */
?>
<div class="title">
    <div class="kicker"><?= h(__('Billing')) ?></div>
    <h1><?= h(__('Invoices')) ?></h1>
    <div class="muted"><?= h(__('Download an invoice as a PDF with the button in its row.')) ?></div>
</div>

<div class="card">
<?php if ($invoices === []) : ?>
    <p class="muted"><?= h(__('No invoices yet.')) ?></p>
<?php else : ?>
    <table class="cards">
        <thead>
            <tr>
                <th><?= h(__('Invoice number')) ?></th>
                <th><?= h(__('Variable symbol')) ?></th>
                <th><?= h(__('Date')) ?></th>
                <th><?= h(__('Due date')) ?></th>
                <th><?= h(__('Paid')) ?></th>
                <th class="num"><?= h(__('Total')) ?></th>
                <th><?= h(__('State')) ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
    <?php foreach ($invoices as $invoice) : ?>
        <?php [$state, $class] = $this->Format->invoiceState($invoice, $today); ?>
            <tr>
                <td>
                    <strong><?= h($invoice->number) ?></strong>
                    <?= $invoice->text !== '' ? '<br><span class="muted">' . h($invoice->text) . '</span>' : '' ?>
                </td>
                <td data-label="<?= h(__('Variable symbol')) ?>"><?= h($invoice->variableSymbol) ?></td>
                <td data-label="<?= h(__('Date')) ?>"><?= h($this->Format->date($invoice->creationDate)) ?></td>
                <td data-label="<?= h(__('Due date')) ?>"><?= h($this->Format->date($invoice->dueDate)) ?></td>
                <td data-label="<?= h(__('Paid')) ?>"><?= h($this->Format->date($invoice->paymentDate)) ?></td>
                <td class="num"><?= h($this->Format->money($invoice->total)) ?></td>
                <td><span class="badge <?= h($class) ?>"><?= h($state) ?></span></td>
                <td class="num"><a class="button small" href="/invoices/<?= h($invoice->id) ?>">PDF</a></td>
            </tr>
    <?php endforeach; ?>
        </tbody>
    <?php if ($due > 0) : ?>
        <tfoot>
            <tr>
                <td colspan="5"><?= h(__('Left to pay')) ?></td>
                <td class="num due"><?= h($this->Format->money($due)) ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    <?php endif; ?>
    </table>
<?php endif; ?>
</div>
