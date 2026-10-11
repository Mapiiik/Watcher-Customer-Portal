
<div class='version'>
&copy;
<?php if (OPERATOR_WEBSITE !== '') { ?>
<a rel="external" href="<?= htmlspecialchars(OPERATOR_WEBSITE) ?>"><?= htmlspecialchars(OPERATOR_NAME) ?></a>
<?php } else { ?>
<?= htmlspecialchars(OPERATOR_NAME) ?>
<?php } ?>
(<?= date("Y") ?>)
</div>
