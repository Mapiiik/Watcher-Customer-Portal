<?php
declare(strict_types=1);

/**
 * What the customer is told about the consents above the checkboxes on the customer data page.
 * $operator is the operator's name, $privacyPolicyUrl its privacy policy (PRIVACY_POLICY_URL).
 *
 * @var string $operator
 * @var string $privacyPolicyUrl
 */
?>
<h4><?= h(__('Consent to the processing of personal data')) ?></h4>
<ul>
    <li><?= h(__('By ticking below I choose which messages I want to receive from {0} (the "Controller") at my contact details. For commercial communications I also give the Controller my consent to sending them and to processing my contact details for this purpose.', $operator)) ?></li>
    <li><?= h(__('I can change or withdraw my choice and consent at any time, here in the customer portal or through the contact details of the Controller. Withdrawing does not affect the lawfulness of the processing before it.')) ?></li>
<?php if ($privacyPolicyUrl !== '') : ?>
    <li><?= __('How the Controller processes personal data is described in its {0}.', '<a href="' . h($privacyPolicyUrl) . '" target="_blank" rel="noopener">' . h(__('privacy policy')) . '</a>') ?></li>
<?php endif; ?>
</ul>
