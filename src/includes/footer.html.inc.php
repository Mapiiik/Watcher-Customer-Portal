
<div class='version'>
&copy;
<?php if (OPERATOR_WEBSITE !== '') { ?>
<a rel="external" href="<?php echo htmlspecialchars(OPERATOR_WEBSITE); ?>"><?php echo htmlspecialchars(OPERATOR_NAME); ?></a>
<?php } else { ?>
<?php echo htmlspecialchars(OPERATOR_NAME); ?>
<?php } ?>
(<?php echo date("Y"); ?>)
</div>
