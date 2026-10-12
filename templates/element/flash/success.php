<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 * @var array<string, mixed> $params
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="alert success" role="alert"><?= $message ?></div>
