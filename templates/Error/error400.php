<?php
/**
 * @var \App\View\AppView $this
 * @var int $code
 */
?>
<div class="title">
    <h1><?= h($code === 404 ? __('This page does not exist.') : __('This request cannot be answered.')) ?></h1>
</div>
<div class="card narrow">
    <p><a href="/"><?= h(__('Back to the customer portal')) ?> →</a></p>
</div>
