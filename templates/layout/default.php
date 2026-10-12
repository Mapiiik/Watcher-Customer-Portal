<?php
/**
 * A page of the portal: the operator's header, the menu of a signed-in customer, the messages,
 * the content and the footer.
 *
 * @var \App\View\AppView $this
 * @var \App\Portal\Operator $operator
 * @var \App\Portal\Profile $profile
 * @var \App\Portal\SignedIn|null $customer
 * @var string|null $title
 */
$current = $this->getRequest()->getParam('controller');
$menu = [
    'Overview' => [__('Overview'), '/'],
    'Invoices' => [__('Invoices'), '/invoices'],
    'Details' => [__('My details'), '/details'],
    'Password' => [__('Password'), '/password'],
];
$branding = fn(string $file): string => $this->Url->build('/branding/' . rawurlencode($file));
?>
<!DOCTYPE html>
<html lang="<?= h($this->Format->language()) ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= h(implode(' | ', array_filter([$profile->pageName(), $title ?? '']))) ?></title>
<?php foreach ($profile->icons() as $icon) : ?>
    <link rel="<?= h($icon['rel']) ?>" href="<?= h($branding($icon['file'])) ?>"<?=
        ($icon['type'] !== '' ? ' type="' . h($icon['type']) . '"' : '')
        . ($icon['sizes'] !== '' ? ' sizes="' . h($icon['sizes']) . '"' : '')
    ?>>
<?php endforeach; ?>
    <?= $this->Html->css('portal') ?>
<?php if ($profile->colors() !== '') : ?>
    <link rel="stylesheet" href="<?= h($branding($profile->colors())) ?>">
<?php endif; ?>
</head>
<body>
<header class="top">
    <a class="logo" href="<?= h($operator->website !== '' ? $operator->website : '/') ?>">
<?php if ($profile->logo() !== '') : ?>
        <img src="<?= h($branding($profile->logo())) ?>" alt="<?= h($operator->name) ?>">
<?php else : ?>
        <span><?= h($operator->name) ?></span>
<?php endif; ?>
    </a>
    <div class="bar">
        <span class="contacts">
<?php if ($operator->supportEmail !== '') : ?>
            <a href="mailto:<?= h($operator->supportEmail) ?>">✉ <?= h($operator->supportEmail) ?></a>
<?php endif; ?>
<?php if ($operator->supportPhone !== '') : ?>
            <a href="tel:<?= h($operator->phoneLink()) ?>">✆ <?= h($operator->supportPhone) ?></a>
<?php endif; ?>
        </span>
<?php if ($operator->website !== '') : ?>
        <a href="<?= h($operator->website) ?>">← <?= h(__('Back to {0}', $operator->websiteName())) ?></a>
<?php endif; ?>
    </div>
    <nav>
<?php if ($customer !== null) : ?>
    <?php foreach ($menu as $controller => [$label, $url]) : ?>
        <a href="<?= h($url) ?>"<?= $controller === $current ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
    <?php endforeach; ?>
<?php else : ?>
        <span aria-current="page"><?= h($profile->heading()) ?></span>
<?php endif; ?>
    </nav>
<?php if ($customer !== null) : ?>
    <?= $this->Form->create(null, [
        'url' => ['controller' => 'SignIn', 'action' => 'signOut'],
        'class' => 'sign-out',
    ]) ?>
    <?= $this->Form->button(__('Sign out'), ['class' => 'cta']) ?>
    <?= $this->Form->end() ?>
<?php endif; ?>
</header>
<main class="wrap">
    <?= $this->Flash->render() ?>
    <?= $this->fetch('content') ?>
</main>
<footer class="site">
    <div class="wrap">
        <span>
            © <?= date('Y') ?> <?= h($operator->name) ?>
            <?= $operator->address !== '' ? ' · ' . h($operator->address) : '' ?>
        </span>
<?php if ($operator->privacyPolicyUrl !== '') : ?>
        <a href="<?= h($operator->privacyPolicyUrl) ?>" target="_blank" rel="noopener">
            <?= h(__('Privacy policy')) ?>
        </a>
<?php endif; ?>
    </div>
</footer>
</body>
</html>
