<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Portal\Operator $operator
 * @var \App\Portal\Profile $profile
 * @var string $login
 */
$contacts = array_filter([$operator->supportPhone, $operator->supportEmail]);
?>
<div class="signin">
    <div class="card">
        <div class="kicker"><?= h($profile->heading()) ?></div>
        <h1><?= h(__('Sign in')) ?></h1>
        <?= $this->Flash->render() ?>
        <?= $this->Form->create(null, ['url' => ['controller' => 'SignIn', 'action' => 'index']]) ?>
        <?= $this->Form->control('login', [
            'label' => __('Login'),
            'value' => $login,
            'autocomplete' => 'username',
            'autofocus' => true,
            'required' => true,
        ]) ?>
        <?= $this->Form->control('password', [
            'type' => 'password',
            'label' => __('Password'),
            'value' => '',
            'autocomplete' => 'current-password',
            'required' => true,
        ]) ?>
        <div class="actions">
            <?= $this->Form->button(__('Sign in'), ['class' => 'button wide']) ?>
        </div>
        <?= $this->Form->end() ?>
<?php if ($contacts !== []) : ?>
        <p class="hint actions"><?= h(__('Forgot your password? Contact us: {0}', implode(', ', $contacts))) ?></p>
<?php endif; ?>
    </div>
</div>
