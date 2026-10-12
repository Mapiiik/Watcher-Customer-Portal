<?php
/**
 * The mail with the changes a customer asks for, to be checked and entered in the CRM. Written
 * as lines, so that no indentation of the template gets into the mail.
 *
 * @var \App\View\AppView $this
 * @var \App\Crm\CustomerDetails $details
 * @var \App\Portal\SignedIn $customer
 * @var \App\Portal\ChangeRequest $request
 * @var string $ip
 */
use Cake\Core\Configure;

// the customer's page in the CRM, for whoever enters the change
$crm = (string)Configure::read('Portal.crm.web');
$lines = [
    __('A customer asks for a change of their details in the customer portal.'),
    '',
    __x('mail', 'Customer') . ': ' . $details->number . ' ' . $details->name,
    __('Login') . ': ' . $customer->login,
];
if ($crm !== '' && $details->id !== '') {
    $lines[] = __('In the CRM: {0}', $crm . '/customers/view/' . rawurlencode($details->id));
}
$lines[] = '';
foreach ($request->lines() as $change) {
    $lines[] = '- ' . $change;
}
if ($request->note !== '') {
    array_push($lines, '', __('Note') . ':', $request->note);
}
array_push($lines, '', __('Sent {0} from {1}', date('Y-m-d H:i'), $ip));

echo implode("\n", $lines) . "\n";
