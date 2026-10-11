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
pageHeaders();

$pageTitle = HTML_PAGE_NAME . ' - ' . 'Menu';
require SRC_DIR . '/includes/head.html.inc.php';

// Init security
$session = new Session();
$session->accessCheck();

// Variables

$pageName = $_SERVER['SCRIPT_NAME'];

$year = date("Y");
$month = date("m");
?>
<body class="center" style="background-image: none;">
<div>
    <?= profileImage($profile['menu_logo']) ?>
    <br />
    <h1><strong><?= htmlspecialchars($profile['heading']) ?></strong></h1>
    <div class="menu">
        <a rel="rightpage" href='introduction.php' title="<?= __('Home page') ?>"><?= __('Home page') ?></a><br />
        <a rel="rightpage" href='reports/customer-data.php' title="<?= __('Customer data') ?>"><?= __('Customer data') ?></a><br />
          <a rel="rightpage" href='reports/invoices.php' title="<?= __('Invoices') ?>"><?= __('Invoices') ?></a><br />
          <a rel="rightpage" href='administration/password.php' title="<?= __('Change password') ?>"><?= __('Change password') ?></a><br />
    </div>
    <div>
        <p>
            <?= __('Signed in') . ': <strong>' . htmlspecialchars((string)$session->login) . '</strong>' ?><br />
            <a rel="toppage" href='index.php?logout=yes' title="<?= __('Sign out') ?>"><?= __('Sign out') ?></a>
        </p>
    </div>
</div>
<?php
//include SRC_DIR . '/includes/footer.html.inc.php';
?>
</body>
</html>
