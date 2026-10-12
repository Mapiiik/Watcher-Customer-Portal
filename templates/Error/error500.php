<?php
/**
 * The log says what went wrong; the customer is told only that the portal is not available.
 *
 * @var \App\View\AppView $this
 * @var \App\Portal\Operator $operator
 */
$contacts = array_filter([$operator->supportPhone, $operator->supportEmail]);
?>
<div class="title">
    <h1><?= h(__('The customer portal is not available right now.')) ?></h1>
</div>
<div class="card narrow">
    <p><?= h(__('Please try again in a while.')) ?></p>
<?php if ($contacts !== []) : ?>
    <p class="muted"><?= h(__('If it does not help, contact us: {0}', implode(', ', $contacts))) ?></p>
<?php endif; ?>
</div>
