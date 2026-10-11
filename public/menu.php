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
    <?php echo profileImage($profile['menu_logo']); ?>
    <br />
    <h1><strong><?php echo htmlspecialchars($profile['heading']); ?></strong></h1>
    <div class="menu">
        <a rel="rightpage" href='introduction.php' title="<?php echo INTRODUCTION_PAGE; ?>"><?php echo INTRODUCTION_PAGE; ?></a><br />
        <a rel="rightpage" href='reports/customer-data.php' title="<?php echo CUSTOMER_DATA; ?>"><?php echo CUSTOMER_DATA; ?></a><br />
          <a rel="rightpage" href='reports/global.traffic.php/<?php echo $year?>/<?php echo $month; ?>/' title="<?php echo TRAFFIC_LIST; ?>"><?php echo TRAFFIC_LIST; ?></a><br />
          <a rel="rightpage" href='reports/invoices.php' title="<?php echo INVOICES_LIST; ?>"><?php echo INVOICES_LIST; ?></a><br />
          <a rel="rightpage" href='administration/password.php' title="<?php echo SET_PASSWORD; ?>"><?php echo SET_PASSWORD; ?></a><br />
    </div>
    <div>
        <p>
            <?php echo LOGGED . ": <strong>" . htmlspecialchars((string)$session->login) . "</strong>";?><br />
            <a rel="toppage" href='index.php?logout=yes' title="<?php echo LOGOUT; ?>"><?php echo LOGOUT; ?></a>
        </p>
    </div>
</div>
<?php
//include SRC_DIR . '/includes/footer.html.inc.php';
?>
</body>
</html>
