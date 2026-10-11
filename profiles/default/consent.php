<?php
// What the customer is told about the consents above the checkboxes on the customer data page
?>
                        <h4><?= __('Consent to the processing of personal data') ?></h4>
                        <ul>
                            <li><?= __('By ticking below I choose which messages I want to receive from %s (the "Controller") at my contact details. For commercial communications I also give the Controller my consent to sending them and to processing my contact details for this purpose.', htmlspecialchars(OPERATOR_NAME)) ?></li>
                            <li><?= __('I can change or withdraw my choice and consent at any time, here in the customer portal or through the contact details of the Controller. Withdrawing does not affect the lawfulness of the processing before it.') ?></li>
<?php if (PRIVACY_POLICY_URL !== '') { ?>
                            <li><?= __('How the Controller processes personal data is described in its %s.', '<a href="' . htmlspecialchars(PRIVACY_POLICY_URL) . '" target="_blank">' . __('privacy policy') . '</a>') ?></li>
<?php } ?>
                        </ul>
