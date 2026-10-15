<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Portal\Operator $operator
 * @var \App\Portal\Profile $profile
 * @var string $login
 */
$contacts = array_filter([
    $operator->supportPhone !== ''
        ? $this->Html->link($operator->supportPhone, 'tel:' . $operator->phoneLink())
        : '',
    $operator->supportEmail !== ''
        ? $this->Html->link($operator->supportEmail, 'mailto:' . $operator->supportEmail)
        : '',
]);
// the sentence is escaped, the links go in after
$help = str_replace('{0}', implode(', ', $contacts), h(__('Need help? Contact us: {0}', '{0}')));
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
        <p class="actions"><?= $this->Html->link(
            __('Forgotten password or new access'),
            ['controller' => 'Access', 'action' => 'index'],
        ) ?></p>
<?php if ($contacts !== []) : ?>
        <p class="hint"><?= $help ?></p>
<?php endif; ?>
    </div>
</div>
