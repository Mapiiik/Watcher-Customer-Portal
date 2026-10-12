<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Crm\CustomerDetails $details
 * @var string $part One of DetailsController::PARTS, or empty for them all.
 */
use App\Crm\Address;
use App\Portal\Labels;

// the fields hold the details as they are, or what the customer had written when back from checking
$shows = static fn(string $section): bool => $part === '' || $part === $section;
// what the change is about, as the card of the details page names it
$heading = match ($part) {
    'customer' => __('Customer details'),
    'contacts' => __('Contacts'),
    'billing' => __('Billing and correspondence'),
    'installation' => __('Installation addresses'),
    'other' => __('Another request'),
    default => __('My details'),
};
// the addresses of each part, with the kinds that may be added there
$billingTypes = [Address::BILLING, Address::PERMANENT, Address::DELIVERY];
$addressParts = array_filter([
    $shows('billing') ? [$details->addressesOf(...$billingTypes), $billingTypes] : null,
    $shows('installation') ? [$details->addressesOf(Address::INSTALLATION), []] : null,
]);
// a row of fields that belong together, in the columns of the form; a wide field takes two, a
// wider one three
$row = static fn(string ...$fields): string => '<div class="columns">' . implode('', $fields) . '</div>';
$wide = ['templateVars' => ['wide' => ' wide']];
// what is added stays in sight when the customer comes back from checking it
$open = fn(string ...$names): string => array_filter(
    $names,
    fn(string $name): bool => trim((string)$this->getRequest()->getData($name)) !== '',
) === [] ? '' : ' open';
$text = function (string $name, string $value, array $options = []): string {
    $options += ['label' => Labels::field($name), 'default' => $value, 'required' => false];

    return $this->Form->control($name, $options);
};
$uses = function (string $prefix, bool $billing, bool $outages, bool $commercial): string {
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
            'default' => $checked,
        ]);
    }

    return $html . '</div>';
};
// the rows of an address: where it is, and the name on it
$addressRows = function (string $prefix, Address $address, array $rows) use ($details, $row, $wide): string {
    $html = '';
    foreach ($rows as $fields) {
        $controls = [];
        foreach ($fields as $field) {
            $options = match ($field) {
                'street' => ['templateVars' => ['wide' => ' wider']],
                'city', 'company' => $wide,
                default => [],
            };
            $controls[] = $field === 'country_id'
                ? $this->Form->control($prefix . '.country_id', [
                    'type' => 'select',
                    'label' => Labels::field('country_id'),
                    'options' => $details->countries,
                    'default' => $address->value('country_id'),
                    'empty' => true,
                ])
                : $this->Form->control($prefix . '.' . $field, $options + [
                    'label' => Labels::field($field),
                    'default' => $address->value($field),
                    'required' => false,
                ]);
        }
        $html .= $row(...$controls);
    }

    return $html;
};
$place = [['street', 'number'], ['zip', 'city', 'country_id']];
$name = [['company'], ['title', 'first_name', 'last_name', 'suffix']];
?>
<div class="title">
    <div class="kicker"><?= h(__('My details')) ?></div>
    <h1><?= h(__('Request a change')) ?></h1>
    <div class="muted"><?= h($part === 'other'
        ? __('Write to us what you would like to change. We will deal with it and let you know.')
        : __('Correct or complete your details and send them. They change once they have been checked.')) ?></div>
</div>

<div class="card">
    <h2><?= h($heading) ?></h2>
    <?= $this->Form->create(null, ['url' => ['controller' => 'Details', 'action' => 'change']]) ?>
    <?= $this->Form->hidden('part', ['value' => $part]) ?>
<?php if ($shows('customer')) : ?>
    <fieldset>
    <?php if ($part === '') : ?>
        <legend><?= h(__('Customer details')) ?></legend>
    <?php endif; ?>
        <?= $row(
            $text('title', $details->value('title')),
            $text('first_name', $details->value('first_name')),
            $text('last_name', $details->value('last_name')),
            $text('suffix', $details->value('suffix')),
        ) ?>
        <?= $row(
            $text('date_of_birth', $details->value('date_of_birth'), ['type' => 'date']),
            $text('identity_card_number', $details->value('identity_card_number')),
            $text('www', $details->value('www'), $wide),
        ) ?>
    </fieldset>
    <fieldset>
        <?= $row(
            $text('company', $details->value('company'), $wide),
            $text('identity_number', $details->value('identity_number')),
            $text('vat_number', $details->value('vat_number')),
        ) ?>
    </fieldset>
<?php endif; ?>

<?php if ($shows('contacts')) : ?>
    <fieldset>
        <legend><?= h(__('E-mail')) ?></legend>
        <p class="hint"><?= h(__('Clear an e-mail to have it removed, and tick what each one is used for.')) ?></p>
    <?php foreach ($details->emails as $email) : ?>
        <?= $this->Form->control('emails.' . $email->id . '.email', [
            'type' => 'email',
            'label' => false,
            'default' => $email->email,
            'required' => false,
        ]) ?>
        <?= $uses('emails.' . $email->id, $email->billing, $email->outages, $email->commercial) ?>
    <?php endforeach; ?>
        <details class="more"<?= $open('emails.new.email') ?>>
            <summary><?= h(__('Add an e-mail')) ?></summary>
            <?= $this->Form->control('emails.new.email', [
                'type' => 'email',
                'label' => false,
                'aria-label' => __('Add an e-mail'),
                'default' => '',
                'required' => false,
            ]) ?>
            <?= $uses('emails.new', true, true, false) ?>
        </details>
    </fieldset>

    <fieldset>
        <legend><?= h(__('Phone')) ?></legend>
        <p class="hint"><?= h(__('Clear a number to have it removed, and tick what each one is used for.')) ?></p>
    <?php foreach ($details->phones as $phone) : ?>
        <?= $this->Form->control('phones.' . $phone->id . '.phone', [
            'type' => 'tel',
            'label' => false,
            'default' => $phone->phone,
            'required' => false,
        ]) ?>
        <?= $uses('phones.' . $phone->id, $phone->billing, $phone->outages, $phone->commercial) ?>
    <?php endforeach; ?>
        <details class="more"<?= $open('phones.new.phone') ?>>
            <summary><?= h(__('Add a phone number')) ?></summary>
            <?= $this->Form->control('phones.new.phone', [
                'type' => 'tel',
                'label' => false,
                'aria-label' => __('Add a phone number'),
                'default' => '',
                'required' => false,
            ]) ?>
            <?= $uses('phones.new', true, true, false) ?>
        </details>
    </fieldset>
<?php endif; ?>

<?php if ($shows('billing')) : ?>
    <fieldset>
    <?php if ($part === '') : ?>
        <legend><?= h(__('Billing details')) ?></legend>
    <?php endif; ?>
        <?= $row($this->Form->control('invoice_delivery_type', $wide + [
            'type' => 'select',
            'label' => Labels::field('invoice_delivery_type'),
            'options' => Labels::invoiceDeliveries(),
            'default' => $details->value('invoice_delivery_type'),
        ])) ?>
        <?= $row(
            $text('bank_account', $details->value('bank_account')),
            $text('bank_code', $details->value('bank_code')),
            $text('bank_name', $details->value('bank_name'), $wide),
        ) ?>
    </fieldset>
<?php endif; ?>

<?php foreach ($addressParts as [$addresses, $addable]) : ?>
    <?php foreach ($addresses as $address) : ?>
        <?php $contracts = $details->contractsAt($address); ?>
    <fieldset>
        <legend><?= h(Labels::addressType($address->type)) ?></legend>
        <?php if ($contracts !== []) : ?>
        <p class="hint"><?= h(__('Contracts: {0}', implode(', ', $contracts))) ?></p>
        <?php endif; ?>
        <?= $this->Form->control('addresses.' . $address->id . '.remove', [
            'type' => 'checkbox',
            'label' => __('Remove this address'),
        ]) ?>
        <details class="more">
            <summary>
                <?= h(__('Name on the address')) ?>: <?= h($address->name() !== '' ? $address->name() : '–') ?>
            </summary>
            <?= $addressRows('addresses.' . $address->id, $address, $name) ?>
        </details>
        <?= $addressRows('addresses.' . $address->id, $address, $place) ?>
    </fieldset>
    <?php endforeach; ?>

    <?php if ($addable !== []) : ?>
    <details class="more add"<?= $open('addresses.new.street', 'addresses.new.number', 'addresses.new.city') ?>>
        <summary><?= h(__('Add an address')) ?></summary>
        <fieldset>
            <?= $row($this->Form->control('addresses.new.type', $wide + [
                'type' => 'select',
                'label' => __('Kind of address'),
                'options' => Labels::addressTypes(...$addable),
                'default' => Address::BILLING,
            ])) ?>
            <?= $addressRows('addresses.new', new Address('', Address::BILLING, []), [...$name, ...$place]) ?>
        </fieldset>
    </details>
    <?php endif; ?>
<?php endforeach; ?>

    <fieldset>
        <legend><?= h(__('Note')) ?></legend>
        <?= $this->Form->control('note', [
            'type' => 'textarea',
            'label' => false,
            'default' => '',
            'required' => false,
        ]) ?>
    </fieldset>

    <div class="actions">
        <?= $this->Form->button(__('Check the request'), ['class' => 'button']) ?>
        <a href="/details"><?= h(__('Back to my details')) ?></a>
    </div>
    <?= $this->Form->end() ?>
</div>
