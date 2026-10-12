<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Portal\Operator $operator
 * @var \App\Portal\Profile $profile
 * @var \App\Crm\CustomerDetails $details
 * @var list<\App\Crm\Invoice> $unpaid
 * @var float $due
 * @var array<string, \App\Portal\PaymentCode> $codes By the id of the invoice.
 * @var list<\App\Crm\Invoice> $latest
 * @var string $today
 */
use App\Crm\Address;

$installation = $details->address(Address::INSTALLATION);
$overdue = count(array_filter($unpaid, static fn($invoice): bool => $invoice->isOverdue($today)));
// the code for changing the provider, where the operator's contracts have one
$withCodes = array_filter(
    $details->connections,
    static fn($connection): bool => $connection->verificationCode !== '',
) !== [];
?>
<div class="title">
    <div class="kicker"><?= h($profile->heading()) ?></div>
    <h1><?= h(__('Hello, {0}', $details->greetingName())) ?></h1>
    <div class="muted"><?= h(implode(' · ', array_filter([
        __('Customer number {0}', $details->number),
        $installation?->street() ?? '',
        $installation?->town() ?? '',
    ]))) ?></div>
</div>

<div class="grid">
<?php if ($unpaid !== []) : ?>
    <div class="card due">
        <div class="head">
            <h2><?= h(__('To pay')) ?></h2>
    <?php if ($overdue > 0) : ?>
            <span class="badge late"><?= h(__n('{0} overdue', '{0} overdue', $overdue, $overdue)) ?></span>
    <?php endif; ?>
        </div>
        <div class="amount due"><?= h($this->Format->money($due)) ?></div>
        <table>
            <thead>
                <tr>
                    <th><?= h(__('Invoice')) ?></th>
                    <th><?= h(__('Variable symbol')) ?></th>
                    <th class="num"><?= h(__('Debt')) ?></th>
                </tr>
            </thead>
            <tbody>
    <?php foreach ($unpaid as $invoice) : ?>
                <tr>
                    <td>
                        <?= h($invoice->number) ?><br>
                        <span class="muted"><?= h(__('due {0}', $this->Format->date($invoice->dueDate))) ?></span>
                    </td>
                    <td><?= h($invoice->variableSymbol) ?></td>
                    <td class="num"><?= h($this->Format->money($invoice->debt)) ?></td>
                </tr>
    <?php endforeach; ?>
            </tbody>
        </table>
    <?php if ($operator->paymentAccount !== '') : ?>
        <dl class="facts actions">
            <dt><?= h(__('Pay to account')) ?></dt>
            <dd><?= h($operator->paymentAccount) ?></dd>
        </dl>
    <?php endif; ?>
    <?php if ($codes !== []) : ?>
        <div class="pay-codes">
            <p class="muted">
                <?= h(__('Scan the code of an invoice in your bank\'s app and the payment fills itself in.')) ?>
            </p>
        <?php foreach ($unpaid as $invoice) : ?>
            <?php if (isset($codes[$invoice->id])) : ?>
            <figure>
                <img
                    src="<?= h($codes[$invoice->id]->src) ?>"
                    width="<?= $codes[$invoice->id]->width ?>"
                    height="<?= $codes[$invoice->id]->height ?>"
                    alt="<?= h(__('Payment code for invoice {0}', $invoice->number)) ?>"
                >
                <figcaption><?= h($invoice->number) ?> · <?= h($this->Format->money($invoice->debt)) ?></figcaption>
            </figure>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
    </div>
<?php else : ?>
    <div class="card settled">
        <div class="head"><h2><?= h(__('To pay')) ?></h2></div>
        <div class="amount settled"><?= h($this->Format->money(0.0)) ?></div>
        <p class="muted actions"><?= h(__('All your invoices are paid. Thank you.')) ?></p>
    </div>
<?php endif; ?>

    <div class="card">
        <div class="head">
            <h2><?= h(__('Latest invoices')) ?></h2>
            <a href="/invoices"><?= h(__('All invoices')) ?> →</a>
        </div>
<?php if ($latest === []) : ?>
        <p class="muted"><?= h(__('No invoices yet.')) ?></p>
<?php else : ?>
        <table>
            <tbody>
    <?php foreach ($latest as $invoice) : ?>
        <?php [$state, $class] = $this->Format->invoiceState($invoice, $today); ?>
                <tr>
                    <td>
                        <?= h($this->Format->date($invoice->creationDate)) ?><br>
                        <span class="muted"><?= h($invoice->number) ?></span>
                    </td>
                    <td class="num">
                        <?= h($this->Format->money($invoice->total)) ?><br>
                        <span class="badge <?= h($class) ?>"><?= h($state) ?></span>
                    </td>
                    <td class="num"><a class="button small" href="/invoices/<?= h($invoice->id) ?>">PDF</a></td>
                </tr>
    <?php endforeach; ?>
            </tbody>
        </table>
<?php endif; ?>
    </div>

<?php if ($details->connections !== []) : ?>
    <div class="card">
        <h2><?= h(__('Connections')) ?></h2>
        <table class="cards">
            <thead>
                <tr>
                    <th><?= h(__('IP address')) ?></th>
                    <th><?= h(__('Contract')) ?></th>
    <?php if ($withCodes) : ?>
                    <th><?= h(__('Subscriber verification code')) ?></th>
    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
    <?php foreach ($details->connections as $connection) : ?>
                <tr>
                    <td><?= h($connection->ipAddress) ?></td>
                    <td data-label="<?= h(__('Contract')) ?>"><?= h($connection->contractNumber) ?></td>
        <?php if ($withCodes) : ?>
                    <td data-label="<?= h(__('Subscriber verification code')) ?>">
                        <?= h($connection->verificationCode) ?>
                    </td>
        <?php endif; ?>
                </tr>
    <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($operator->supportPhone !== '' || $operator->supportEmail !== '' || $operator->website !== '') : ?>
    <div class="card">
        <h2><?= h(__('Need help?')) ?></h2>
        <dl class="facts">
    <?php if ($operator->supportPhone !== '') : ?>
            <dt><?= h(__('Phone')) ?></dt>
            <dd><a href="tel:<?= h($operator->phoneLink()) ?>"><?= h($operator->supportPhone) ?></a></dd>
    <?php endif; ?>
    <?php if ($operator->supportEmail !== '') : ?>
            <dt><?= h(__('E-mail')) ?></dt>
            <dd><a href="mailto:<?= h($operator->supportEmail) ?>"><?= h($operator->supportEmail) ?></a></dd>
    <?php endif; ?>
    <?php if ($operator->website !== '') : ?>
            <dt><?= h(__('Website')) ?></dt>
            <dd><a href="<?= h($operator->website) ?>"><?= h($operator->websiteName()) ?></a></dd>
    <?php endif; ?>
        </dl>
    </div>
<?php endif; ?>
</div>
