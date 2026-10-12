<?php
/**
 * @var \App\View\AppView $this
 * @var int $minLength
 */
?>
<div class="title">
    <div class="kicker"><?= h(__('Signing in')) ?></div>
    <h1><?= h(__('Change password')) ?></h1>
</div>

<div class="card narrow">
    <?= $this->Form->create(null, ['url' => ['controller' => 'Password', 'action' => 'index']]) ?>
    <?= $this->Form->control('current_password', [
        'type' => 'password',
        'label' => __('Current password'),
        'value' => '',
        'autocomplete' => 'current-password',
        'required' => true,
    ]) ?>
    <?= $this->Form->control('new_password', [
        'type' => 'password',
        'label' => __('New password'),
        'value' => '',
        'autocomplete' => 'new-password',
        'minlength' => $minLength,
        'required' => true,
    ]) ?>
    <p class="hint"><?= h(__('At least {0} characters.', $minLength)) ?></p>
    <?= $this->Form->control('new_password_again', [
        'type' => 'password',
        'label' => __('Confirm password'),
        'value' => '',
        'autocomplete' => 'new-password',
        'required' => true,
    ]) ?>
    <div class="actions">
        <?= $this->Form->button(__('Change password'), ['class' => 'button']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
