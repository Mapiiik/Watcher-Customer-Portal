<?php
/**
 * What a customer who asked for a new password is told when their access is locked on purpose.
 * Written as lines, so that no indentation of the template gets into the mail.
 *
 * @var \App\View\AppView $this
 * @var \App\Portal\Operator $operator
 * @var string $customerNumber
 */

$contacts = array_filter([$operator->supportPhone, $operator->supportEmail]);
$lines = [
    __('You have asked for a new password, or for access, to the customer portal of {0}.', $operator->name),
    __x('mail', 'Customer') . ': ' . $customerNumber,
    '',
    __('Your access to the customer portal has been locked on purpose, so no new password can be set.'),
    $contacts !== []
        ? __('Please contact us: {0}', implode(', ', $contacts))
        : __('Please contact us.'),
    '',
    __('If you did not ask for it, you need not do anything.'),
];

echo implode("\n", $lines) . "\n";
