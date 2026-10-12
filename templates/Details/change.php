<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Crm\CustomerDetails $details
 */
use App\Crm\Address;
use App\Portal\Labels;

$text = function (string $name, string $value, array $options = []): string {
    $options += ['label' => Labels::field($name), 'value' => $value, 'required' => false];

    return $this->Form->control($name, $options);
};
$emailUses = function (string $prefix, bool $billing, bool $outages, bool $commercial): string {
    $html = '<div class="uses">';
    $uses = [
        'billing' => [__('billing'), $billing],
        'outages' => [__('outages'), $outages],
        'commercial' => [__('commercial messages'), $commercial],
    ];
    foreach ($uses as $use => [$label, $checked]) {
        $html .= $this->Form->control($prefix . '.' . $use, [
            'type' => 'checkbox',
            'label' => $label,
            'checked' => $checked,
        ]);
    }

    return $html . '</div>';
};
$addressFields = function (string $prefix, Address $address) use ($details): string {
    $html = '<div class="columns">';
    foreach (['company', 'title', 'first_name', 'last_name', 'suffix', 'street', 'number', 'zip', 'city'] as $field) {
        $html .= $this->Form->control($prefix . '.' . $field, [
            'label' => Labels::field($field),
            'value' => $address->value($field),
            'required' => false,
        ]);
    }
    $html .= $this->Form->control($prefix . '.country_id', [
        'type' => 'select',
        'label' => Labels::field('country_id'),
        'options' => $details->countries,
        'value' => $address->value('country_id'),
        'empty' => true,
    ]);

    return $html . '</div>';
};
?>
<div class="title">
    <div class="kicker"><?= h(__('My details')) ?></div>
    <h1><?= h(__('Request a change')) ?></h1>
    <div class="muted"><?= h(__(
        'Correct or complete your details and send them. They change once they have been checked.',
    )) ?></div>
</div>

<div class="card">
    <?= $this->Form->create(null, ['url' => ['controller' => 'Details', 'action' => 'change']]) ?>
    <fieldset>
        <legend><?= h(__('Customer')) ?></legend>
        <div class="columns">
<?php foreach (['company', 'title', 'first_name', 'last_name', 'suffix', 'identity_card_number', 'www'] as $field) : ?>
            <?= $text($field, $details->value($field)) ?>
<?php endforeach; ?>
            <?= $text('date_of_birth', $details->value('date_of_birth'), ['type' => 'date']) ?>
        </div>
    </fieldset>

    <fieldset>
        <legend><?= h(__('E-mail')) ?></legend>
        <p class="hint"><?= h(__('Clear an address to have it removed, and tick what each one is used for.')) ?></p>
<?php foreach ($details->emails as $email) : ?>
        <?= $this->Form->control('emails.' . $email->id . '.email', [
            'type' => 'email',
            'label' => false,
            'value' => $email->email,
            'required' => false,
        ]) ?>
        <?= $emailUses('emails.' . $email->id, $email->billing, $email->outages, $email->commercial) ?>
<?php endforeach; ?>
        <?= $this->Form->control('emails.new.email', [
            'type' => 'email',
            'label' => __('Another e-mail address'),
            'value' => '',
            'required' => false,
        ]) ?>
        <?= $emailUses('emails.new', true, true, false) ?>
    </fieldset>

    <fieldset>
        <legend><?= h(__('Phone')) ?></legend>
        <div class="columns">
<?php foreach ($details->phones as $phone) : ?>
            <?= $this->Form->control('phones.' . $phone->id . '.phone', [
                'type' => 'tel',
                'label' => false,
                'value' => $phone->phone,
                'required' => false,
            ]) ?>
<?php endforeach; ?>
            <?= $this->Form->control('phones.new.phone', [
                'type' => 'tel',
                'label' => __('Another phone number'),
                'value' => '',
                'required' => false,
            ]) ?>
        </div>
    </fieldset>

    <fieldset>
        <legend><?= h(__('Billing details')) ?></legend>
        <div class="columns">
            <?= $text('identity_number', $details->value('identity_number')) ?>
            <?= $text('vat_number', $details->value('vat_number')) ?>
            <?= $this->Form->control('invoice_delivery_type', [
                'type' => 'select',
                'label' => Labels::field('invoice_delivery_type'),
                'options' => Labels::invoiceDeliveries(),
                'value' => $details->value('invoice_delivery_type'),
            ]) ?>
            <?= $text('bank_name', $details->value('bank_name')) ?>
            <?= $text('bank_account', $details->value('bank_account')) ?>
            <?= $text('bank_code', $details->value('bank_code')) ?>
        </div>
    </fieldset>

<?php foreach ($details->addresses as $address) : ?>
    <fieldset>
        <legend><?= h(Labels::addressType($address->type)) ?></legend>
        <?= $this->Form->control('addresses.' . $address->id . '.remove', [
            'type' => 'checkbox',
            'label' => __('Remove this address'),
        ]) ?>
        <?= $addressFields('addresses.' . $address->id, $address) ?>
    </fieldset>
<?php endforeach; ?>

    <fieldset>
        <legend><?= h(__('Another address')) ?></legend>
        <?= $this->Form->control('addresses.new.type', [
            'type' => 'select',
            'label' => __('Kind of address'),
            'options' => Labels::addressTypes(),
            'value' => Address::BILLING,
        ]) ?>
        <?= $addressFields('addresses.new', new Address('', Address::BILLING, [])) ?>
    </fieldset>

    <fieldset>
        <legend><?= h(__('Note')) ?></legend>
        <?= $this->Form->control('note', [
            'type' => 'textarea',
            'label' => false,
            'value' => '',
            'required' => false,
        ]) ?>
    </fieldset>

    <div class="actions">
        <?= $this->Form->button(__('Send for processing'), ['class' => 'button']) ?>
        <a href="/details"><?= h(__('Back to my details')) ?></a>
    </div>
    <?= $this->Form->end() ?>
</div>
