<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Portal\Profile $profile
 * @var string $number
 * @var string $email
 */
?>
<div class="signin">
    <div class="card">
        <div class="kicker"><?= h($profile->heading()) ?></div>
        <h1><?= h(__('Forgotten password or new access')) ?></h1>
        <?= $this->Flash->render() ?>
        <p class="hint"><?= h(__(
            'Type your customer number and an e-mail you have given us. We send a link to set a password '
            . 'to it, and where you have no login yet, one is made for you.',
        )) ?></p>
        <?= $this->Form->create(null, ['url' => ['controller' => 'Access', 'action' => 'index']]) ?>
        <?= $this->Form->control('customer_number', [
            'label' => __('Customer number or contract number'),
            'value' => $number,
            'inputmode' => 'numeric',
            'autocomplete' => 'off',
            'autofocus' => true,
            'required' => true,
        ]) ?>
        <?= $this->Form->control('email', [
            'type' => 'email',
            'label' => __('E-mail'),
            'value' => $email,
            'autocomplete' => 'email',
            'required' => true,
        ]) ?>
        <div class="actions">
            <?= $this->Form->button(__('Send link'), ['class' => 'button wide']) ?>
        </div>
        <?= $this->Form->end() ?>
        <p class="actions"><?= $this->Html->link(
            __('Back to signing in'),
            ['controller' => 'SignIn', 'action' => 'index'],
        ) ?></p>
    </div>
</div>
