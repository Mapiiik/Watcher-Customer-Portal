<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= LANGUAGE ?>" >
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="Content-language" content="<?= LANGUAGE ?>" />
	<meta name="author" content="Martin Patočka [www.mapik.net]" />
	<meta name="description" content="<?= htmlspecialchars(HTML_PAGE_NAME) ?>" />
	<meta name="robots" content="noindex, nofollow" />

	<title><?= htmlspecialchars($pageTitle) ?></title>
<?= profileIcons($profile['icons']) ?>
	<link href="<?= PREFIX ?>/style/portal.css" rel="stylesheet" type="text/css" />
<?php if ($profile['colors'] !== null) { ?>
	<link href="<?= brandingUrl($profile['colors']) ?>" rel="stylesheet" type="text/css" />
<?php } ?>
	<script type="text/javascript" src="<?= PREFIX ?>/js/common.js"></script>
</head>
