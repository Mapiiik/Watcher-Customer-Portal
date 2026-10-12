<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Portal\Operator $operator
 * @var \App\Portal\Profile $profile
 * @var \App\Crm\CustomerDetails $details
 */
use App\Portal\Labels;

$value = static fn(string $field): string => $details->value($field) !== '' ? $details->value($field) : '–';
$bankCode = $details->value('bank_code');
$bankAccount = trim($details->value('bank_account') . ($bankCode !== '' ? '/' . $bankCode : ''));
?>
<div class="title">
    <div class="kicker"><?= h(__('Customer')) ?></div>
    <h1><?= h(__('My details')) ?></h1>
    <div class="muted"><?= h(__(
        'Changes of your details are checked before they apply. Your consents are saved straight away.',
    )) ?></div>
</div>

<div class="grid">
    <div class="card">
        <div class="head">
            <h2><?= h(__('Customer')) ?></h2>
            <a href="/details/change"><?= h(__('Request a change')) ?> →</a>
        </div>
        <dl class="facts">
            <dt><?= h(__('Name')) ?></dt>
            <dd><?= h($details->personName() !== '' ? $details->personName() : '–') ?></dd>
<?php if ($details->value('company') !== '') : ?>
            <dt><?= h(Labels::field('company')) ?></dt>
            <dd><?= h($details->value('company')) ?></dd>
<?php endif; ?>
            <dt><?= h(__('Customer number')) ?></dt>
            <dd><?= h($details->number) ?></dd>
            <dt><?= h(Labels::field('date_of_birth')) ?></dt>
            <dd><?= h($this->Format->date($details->value('date_of_birth'))) ?></dd>
            <dt><?= h(Labels::field('identity_card_number')) ?></dt>
            <dd><?= h($value('identity_card_number')) ?></dd>
            <dt><?= h(__('E-mail')) ?></dt>
            <dd>
<?php foreach ($details->emails as $email) : ?>
                <?= h($email->email) ?>
    <?php $uses = Labels::emailUses($email->billing, $email->outages, $email->commercial); ?>
                <?= $uses !== '' ? '<span class="muted">(' . h($uses) . ')</span>' : '' ?><br>
<?php endforeach; ?>
                <?= $details->emails === [] ? '–' : '' ?>
            </dd>
            <dt><?= h(__('Phone')) ?></dt>
            <dd><?= $details->phones === []
                ? '–'
                : implode('<br>', array_map(static fn($phone): string => h($phone->phone), $details->phones)) ?></dd>
            <dt><?= h(Labels::field('www')) ?></dt>
            <dd><?= h($value('www')) ?></dd>
        </dl>
    </div>

    <div class="card">
        <div class="head">
            <h2><?= h(__('Billing and addresses')) ?></h2>
            <a href="/details/change"><?= h(__('Request a change')) ?> →</a>
        </div>
        <dl class="facts">
            <dt><?= h(Labels::field('invoice_delivery_type')) ?></dt>
            <dd><?= h(Labels::invoiceDelivery($details->value('invoice_delivery_type'))) ?></dd>
            <dt><?= h(Labels::field('identity_number')) ?></dt>
            <dd><?= h($value('identity_number')) ?></dd>
            <dt><?= h(Labels::field('vat_number')) ?></dt>
            <dd><?= h($value('vat_number')) ?></dd>
            <dt><?= h(Labels::field('bank_account')) ?></dt>
            <dd>
                <?= h($bankAccount !== '' ? $bankAccount : '–') ?>
                <?= $details->value('bank_name') !== ''
                    ? '<br><span class="muted">' . h($details->value('bank_name')) . '</span>'
                    : '' ?>
            </dd>
<?php foreach ($details->addresses as $address) : ?>
            <dt><?= h(Labels::addressType($address->type)) ?></dt>
            <dd><?= implode('<br>', array_map('h', array_filter([
                $address->name(),
                $address->street(),
                $address->town(),
                $details->country($address->value('country_id')),
            ]))) ?></dd>
<?php endforeach; ?>
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
