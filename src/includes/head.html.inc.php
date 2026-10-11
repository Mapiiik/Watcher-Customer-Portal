<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo LANGUAGE; ?>" >
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="Content-language" content="<?php echo LANGUAGE; ?>" />
	<meta name="author" content="Martin Patočka [www.mapik.net]" />
	<meta name="description" content="<?php echo htmlspecialchars(HTML_PAGE_NAME); ?>" />
	<meta name="robots" content="noindex, nofollow" />

	<title><?php echo htmlspecialchars($pageTitle); ?></title>
	<link href="<?php echo PREFIX; ?>/style/portal.css" rel="stylesheet" type="text/css" />
<?php if ($profile['colors'] !== null) { ?>
	<link href="<?php echo brandingUrl($profile['colors']); ?>" rel="stylesheet" type="text/css" />
<?php } ?>
	<script type="text/javascript" src="<?php echo PREFIX; ?>/js/common.js"></script>
</head>
