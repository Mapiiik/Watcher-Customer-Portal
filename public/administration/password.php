<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';

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

$pageTitle = HTML_PAGE_NAME . ' - ' . __('Change password');
require SRC_DIR . '/includes/head.html.inc.php';

// Init security
$session = new Session();
$session->accessCheck();

// Variables
$pageName = $_SERVER['SCRIPT_NAME'];
$sqllisting = false;

?>
<body>
<?php
// START Password
if (isset($_POST['set_password']) && $_POST['set_password'] == 'yes' && isset($_POST['set_password_1']) && isset($_POST['set_password_2']) && !($_POST['set_password_1'] == '' && $_POST['set_password_2'] == ''))
{
	echo __('Change password') . '...';
	if ($_POST['set_password_1'] == $_POST['set_password_2'])
	{
		// Change password
		$crm = new Crm();
		if ($session->setPassword($crm, $_POST['set_password_1']))
		{
			echo "OK";
		}
		else
		{
			$session->crmFailed($crm);
		}
	}
	else echo "<div class='fred'>" . __('The passwords do not match!') . "</div>";
}
?>

<form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="post" enctype="multipart/form-data">
<h1><?= __('Change password') ?></h1>
  <table>
    <tr>
	  <td><?= __('New password') . ":" ?></td>
	  <td><input name="set_password_1" type="password" value="" /></td>
    </tr>
    <tr>
	  <td><?= __('Confirm password') . ":" ?></td>
	  <td><input name="set_password_2" type="password" value="" /></td>
    </tr>
    <tr>
	  <td><input type="reset"></td></td>
	  <td><input name="set_password" type="hidden" value="yes" /><input type="submit" /></td></td>
    </tr>
  </table>
</form>
<?php
// END Password
?>
</body>
</html>
