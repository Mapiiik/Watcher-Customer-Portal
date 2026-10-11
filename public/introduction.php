<?php
require dirname(__DIR__) . '/src/bootstrap.php';

// Report ALL if in debug mode
if (DEBUG) {
  error_reporting(E_ALL);
} else {
  error_reporting(E_ERROR);
}

// Session

session_start();
header("Pragma: no-cache");
header("Content-type: text/html; charset=UTF-8");
// iframe security
header("Content-Security-Policy: frame-ancestors 'self'");
header("X-Frame-Options: SAMEORIGIN");

$pageTitle = HTML_PAGE_NAME . ' - ' . __('Home page');
require SRC_DIR . '/includes/head.html.inc.php';

// Init security
$session = new Session();
$session->accessCheck();
?>
<body>
<?php require profilePart('introduction'); ?>
</body>
</html>