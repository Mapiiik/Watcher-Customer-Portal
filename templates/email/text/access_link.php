<?php
/**
 * The link a customer asked for to set a password, or to get a login. Written as lines, so that
 * no indentation of the template gets into the mail.
 *
 * @var \App\View\AppView $this
 * @var \App\Portal\Operator $operator
 * @var string $customerNumber
 * @var string $link
 */

$lines = [
    __('You have asked for a new password, or for access, to the customer portal of {0}.', $operator->name),
    __x('mail', 'Customer') . ': ' . $customerNumber,
    '',
    __('Set your password here. The link works for an hour and only once:'),
    $link,
    '',
    __('If you did not ask for it, you need not do anything. Your password stays as it is.'),
];

echo implode("\n", $lines) . "\n";
