<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Portal\ChangeRequest $change
 * @var string $part The part of the details the change is for, to go back to.
 */
?>
<div class="title">
    <div class="kicker"><?= h(__('My details')) ?></div>
    <h1><?= h(__('Check your request')) ?></h1>
    <div class="muted"><?= h(__('This is what we will receive. Your details change once we have checked it.')) ?></div>
</div>

<div class="card narrow">
<?php if ($change->lines() !== []) : ?>
    <h2><?= h(__('Changes')) ?></h2>
    <ul class="changes">
    <?php foreach ($change->lines() as $line) : ?>
        <li><?= h($line) ?></li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php if ($change->note !== '') : ?>
    <h2><?= h(__('Note')) ?></h2>
    <p class="note"><?= nl2br(h($change->note)) ?></p>
<?php endif; ?>
    <?= $this->Form->create(null, ['url' => ['controller' => 'Details', 'action' => 'send']]) ?>
    <div class="actions">
        <?= $this->Form->button(__('Send for processing'), ['class' => 'button']) ?>
        <a href="/details/change?<?= h(http_build_query(['part' => $part, 'edit' => 1])) ?>"><?= h(__('Edit')) ?></a>
    </div>
    <?= $this->Form->end() ?>
</div>
