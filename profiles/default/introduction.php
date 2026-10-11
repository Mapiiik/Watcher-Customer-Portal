<?php
// The page a customer lands on after signing in
?>
<h1><?= __('You have signed in to the customer portal.') ?></h1>
<p><?= __('The menu offers the following:') ?></p>
<span><b><?= __('Customer data') ?>:</b></span>
<table width="100%">
<tr><td>
<p><?= __('Here are your details. You can correct them and send them for processing: they change only after they have been checked.') ?></p>
<p><?= __('You can also give your consent to the processing of personal data here.') ?></p>
<p><?= __('You also see the IP addresses assigned to you.') ?></p>
</td></tr>
</table>
<span><b><?= __('Invoices') ?>:</b></span>
<table width="100%">
<tr><td>
<p><?= __('Here you see all invoices issued to you: the variable symbol, the date of issue, the due date, the date of payment and the amount. An amount in the Debt column means the invoice has not been paid.') ?></p>
<p><?= __('You can download an invoice as a PDF by clicking its number.') ?></p>
</td></tr>
</table>
<span><b><?= __('Change password') ?>:</b></span>
<table width="100%">
<tr><td>
<p><?= __('Here you can change the password you sign in with.') ?></p>
</td></tr>
</table>
