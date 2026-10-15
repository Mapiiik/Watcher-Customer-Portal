<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Portal\Profile $profile
 * @var \App\Crm\AccessOffer $offer
 * @var string $token
 */
?>
<div class="signin">
    <div class="card">
        <div class="kicker"><?= h($profile->heading()) ?></div>
        <h1><?= h(__('Set a password')) ?></h1>
        <?= $this->Flash->render() ?>
        <p class="hint"><?= h(
            __x('mail', 'Customer') . ': ' . $offer->customerNumber . ' ' . $offer->customerName,
        ) ?></p>
        <?= $this->Form->create(null, [
            'url' => ['controller' => 'Access', 'action' => 'setPassword', '?' => ['token' => $token]],
        ]) ?>
<?php if (count($offer->logins) > 1) : ?>
        <?= $this->Form->control('login_id', [
            'type' => 'select',
            'label' => __('Login'),
            'options' => $offer->logins,
            'required' => true,
        ]) ?>
<?php elseif ($offer->logins !== []) : ?>
        <p><?= h(__('Login') . ': ' . implode('', $offer->logins)) ?></p>
<?php else : ?>
        <p><?= h(__('You have no login yet. It will be {0}.', $offer->newLogin)) ?></p>
<?php endif; ?>
        <?= $this->Form->control('password', [
            'type' => 'password',
            'label' => __('New password'),
            'value' => '',
            'autocomplete' => 'new-password',
            'minlength' => $offer->passwordMinLength,
            'required' => true,
        ]) ?>
        <p class="hint"><?= h(__('At least {0} characters.', $offer->passwordMinLength)) ?></p>
        <?= $this->Form->control('password_again', [
            'type' => 'password',
            'label' => __('Confirm password'),
            'value' => '',
            'autocomplete' => 'new-password',
            'required' => true,
        ]) ?>
        <div class="actions">
            <?= $this->Form->button(__('Set password and sign in'), ['class' => 'button wide']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
