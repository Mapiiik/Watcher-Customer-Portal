<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Portal\Operator $operator
 * @var \App\Portal\Profile $profile
 * @var \App\Crm\CustomerDetails $details
 */
use App\Crm\Address;
use App\Portal\Labels;

// an address in lines: the name on it, the street, the town and the country
$addressLines = static fn(Address $address): string => implode('<br>', array_map('h', array_filter([
    $address->name(),
    $address->street(),
    $address->town(),
    $details->country($address->value('country_id')),
])));
$bankCode = $details->value('bank_code');
$bankAccount = trim($details->value('bank_account') . ($bankCode !== '' ? '/' . $bankCode : ''));
// the company the customer is, if any: set apart from the person
$business = array_filter(
    ['company', 'identity_number', 'vat_number'],
    static fn(string $field): bool => $details->value($field) !== '',
);
// the head of a card, with the link to ask for a change of what it shows
$head = static fn(string $title, string $part): string => '<div class="head"><h2>' . h($title) . '</h2>'
    . '<a class="button small next" href="/details/change?part=' . h($part) . '">'
    . h(__('Request a change')) . '</a></div>';
?>
<div class="title with-action">
    <div>
        <div class="kicker"><?= h(__('Customer')) ?></div>
        <h1><?= h(__('My details')) ?></h1>
        <div class="muted">
            <?= h(__(
                'Changes of your details are checked before they apply. Your consents are saved straight away.',
            )) ?>
        </div>
    </div>
    <a class="button small next" href="/details/change?part=other"><?= h(__('Another request')) ?></a>
</div>

<div class="grid">
    <div class="card">
        <?= $head(__('Customer details'), 'customer') ?>
        <dl class="facts">
            <dt><?= h(__('Customer number')) ?></dt>
            <dd><?= h($details->number) ?></dd>
<?php if ($details->personName() !== '' || $details->value('company') === '') : ?>
            <dt><?= h(__('Name')) ?></dt>
            <dd><?= h($details->personName() !== '' ? $details->personName() : '–') ?></dd>
<?php endif; ?>
<?php foreach (['date_of_birth', 'identity_card_number', 'www'] as $field) : ?>
    <?php if ($details->value($field) !== '') : ?>
            <dt><?= h(Labels::field($field)) ?></dt>
            <dd><?= h($field === 'date_of_birth'
                ? $this->Format->date($details->value($field))
                : $details->value($field)) ?></dd>
    <?php endif; ?>
<?php endforeach; ?>
<?php foreach (array_values($business) as $i => $field) : ?>
    <?php $apart = $i === 0 ? ' class="apart"' : ''; ?>
            <dt<?= $apart ?>><?= h(Labels::field($field)) ?></dt>
            <dd<?= $apart ?>><?= h($details->value($field)) ?></dd>
<?php endforeach; ?>
        </dl>
    </div>

    <div class="card">
        <?= $head(__('Contacts'), 'contacts') ?>
        <dl class="facts">
            <dt><?= h(__('E-mail')) ?></dt>
            <dd>
<?php foreach ($details->emails as $email) : ?>
    <?php $uses = Labels::uses($email->billing, $email->outages, $email->commercial); ?>
                <div>
                    <?= h($email->email) ?>
                    <?= $uses !== '' ? '<div class="muted small">(' . h($uses) . ')</div>' : '' ?>
                </div>
<?php endforeach; ?>
                <?= $details->emails === [] ? '–' : '' ?>
            </dd>
            <dt><?= h(__('Phone')) ?></dt>
            <dd>
<?php foreach ($details->phones as $phone) : ?>
    <?php $uses = Labels::uses($phone->billing, $phone->outages, $phone->commercial); ?>
                <div>
                    <?= h($phone->phone) ?>
                    <?= $uses !== '' ? '<div class="muted small">(' . h($uses) . ')</div>' : '' ?>
                </div>
<?php endforeach; ?>
                <?= $details->phones === [] ? '–' : '' ?>
            </dd>
        </dl>
    </div>

    <div class="card">
        <?= $head(__('Billing and correspondence'), 'billing') ?>
        <dl class="facts">
            <dt><?= h(Labels::field('invoice_delivery_type')) ?></dt>
            <dd><?= h(Labels::invoiceDelivery($details->value('invoice_delivery_type'))) ?></dd>
            <dt><?= h(Labels::field('bank_account')) ?></dt>
            <dd>
                <?= h($bankAccount !== '' ? $bankAccount : '–') ?>
                <?= $details->value('bank_name') !== ''
                    ? '<br><span class="muted">' . h($details->value('bank_name')) . '</span>'
                    : '' ?>
            </dd>
<?php foreach ($details->addressesOf(Address::BILLING, Address::PERMANENT, Address::DELIVERY) as $address) : ?>
            <dt class="apart"><?= h(Labels::addressType($address->type)) ?></dt>
            <dd class="apart"><?= $addressLines($address) ?></dd>
<?php endforeach; ?>
        </dl>
<?php if ($details->addressesOf(Address::BILLING, Address::PERMANENT) === []) : ?>
        <p class="muted small actions">
            <?= h(__('Without a billing address, invoices go to the installation address.')) ?>
        </p>
<?php endif; ?>
    </div>

    <div class="card">
        <?= $head(__('Installation addresses'), 'installation') ?>
        <dl class="facts">
<?php foreach ($details->addressesOf(Address::INSTALLATION) as $i => $address) : ?>
    <?php
    $contracts = $details->contractsAt($address);
    $apart = $i > 0 ? ' class="apart"' : '';
    ?>
            <dt<?= $apart ?>><?= h(Labels::addressType($address->type)) ?></dt>
            <dd<?= $apart ?>>
                <?= $addressLines($address) ?>
    <?php if ($contracts !== []) : ?>
                <div class="muted small"><?= h(__('Contracts: {0}', implode(', ', $contracts))) ?></div>
    <?php endif; ?>
            </dd>
<?php endforeach; ?>
<?php if ($details->addressesOf(Address::INSTALLATION) === []) : ?>
            <dt><?= h(__('Installation address')) ?></dt>
            <dd>–</dd>
<?php endif; ?>
        </dl>
    </div>

    <div class="card wide">
        <h2><?= h(__('Consents and messages')) ?></h2>
        <div class="consent-text">
<?php (static function (string $file, string $operator, string $privacyPolicyUrl): void {
    require $file;
})($profile->part('consent'), $operator->name, $operator->privacyPolicyUrl); ?>
        </div>
        <?= $this->Form->create(null, ['url' => ['controller' => 'Details', 'action' => 'consents']]) ?>
        <div class="choice">
            <?= $this->Form->control('agree_mailing_billing', [
                'type' => 'checkbox',
                'checked' => $details->consents->billing,
                'label' => __('I want to receive correspondence about the monthly billing'),
            ]) ?>
        </div>
        <div class="choice">
            <?= $this->Form->control('agree_mailing_outages', [
                'type' => 'checkbox',
                'checked' => $details->consents->outages,
                'label' => __('I want to receive information about outages and faults'),
            ]) ?>
        </div>
        <div class="choice">
            <?= $this->Form->control('agree_mailing_commercial', [
                'type' => 'checkbox',
                'checked' => $details->consents->commercial,
                'label' => __('I agree to receive commercial communications'),
            ]) ?>
        </div>
        <div class="actions">
            <?= $this->Form->button(__('Save consents'), ['class' => 'button']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
