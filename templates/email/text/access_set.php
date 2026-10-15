<?php
/**
 * What a customer is told once a password has been set with a link, so that one they did not
 * set themselves does not go unnoticed. Written as lines, so that no indentation of the template
 * gets into the mail.
 *
 * @var \App\View\AppView $this
 * @var \App\Portal\Operator $operator
 * @var string $customerNumber
 * @var string $login
 * @var bool $made
 * @var string $ip
 */

$contacts = array_filter([$operator->supportPhone, $operator->supportEmail]);
$lines = [
    $made
        ? __('A login to the customer portal of {0} has been made for you.', $operator->name)
        : __('The password of your login to the customer portal of {0} has been changed.', $operator->name),
    __x('mail', 'Customer') . ': ' . $customerNumber,
    __('Login') . ': ' . $login,
    __('At {0}, from the address {1}', date('Y-m-d H:i'), $ip),
    '',
    $contacts !== []
        ? __('If it was not you, contact us at once: {0}', implode(', ', $contacts))
        : __('If it was not you, contact us at once.'),
];

echo implode("\n", $lines) . "\n";
