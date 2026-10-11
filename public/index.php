<?php
require dirname(__DIR__) . '/src/bootstrap.php';



session_start();
pageHeaders();

// Variables
$pageTitle = HTML_PAGE_NAME;
$pageName = $_SERVER['SCRIPT_NAME'];

$year = date("Y");
$month = date("m");

// Init
$session = new Session();

// Report ALL if in debug mode
if (DEBUG)
{
  error_reporting(E_ALL);
}
else
{
  error_reporting(E_ERROR);
}

// Login
if(isset($_POST['login_me']) && $_POST['login_me'] == 'yes' && isset($_POST['login']) && isset($_POST['password']))
{
	$session->login(new Crm(), $_POST['login'], $_POST['password']);
}
// Logout
if(isset($_GET['logout']) && $_GET['logout'] == 'yes')
{
	$session->logout();
}
// Access granted
if($session->isLogged())
{
?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
     <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= LANGUAGE ?>" lang="<?= LANGUAGE ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="author" content="Martin Patočka [www.mapik.net]" />
	<meta name="description" content="<?= htmlspecialchars(HTML_PAGE_NAME) ?>" />
	<meta name="robots" content="noindex, nofollow" />

	<title><?= htmlspecialchars($pageTitle) ?></title>
<?= profileIcons($profile['icons']) ?>
	<link href="<?= PREFIX ?>/style/portal.css" rel="stylesheet" type="text/css" />
</head>
<frameset cols="200,*">
  <frame frameborder="0" framespacing="0" src="<?= PREFIX ?>/menu.php" name="_menu" scrolling="no" />
<?php
if (isset($_SESSION['blockedURL']) && preg_match('#^/(?![/\\\\])#', $_SESSION['blockedURL'])) {
?>
  <frame frameborder="0" framespacing="0" src="<?= htmlspecialchars($_SESSION['blockedURL']) ?>" name="_right" scrolling="auto" />
<?php
    unset($_SESSION['blockedURL']);
} else { ?>
  <frame frameborder="0" framespacing="0" src="<?= PREFIX ?>/introduction.php/<?= $year ?>/<?= $month ?>/" name="_right" scrolling="auto" />
<?php } ?>
  <noframes>
    <body><p><?= __('This page uses frames, which your browser does not support.') ?></p></body>
  </noframes>
</frameset>
<?php
}
else
{
  require SRC_DIR . '/includes/head.html.inc.php';
	?>
	<body>
	<p><?= profileImage($profile['logo']) ?></p>
        <div class="center">
            <h1><?= htmlspecialchars($profile['heading']) ?></h1>
            <form id="login_form" action="<?= htmlspecialchars($pageName) ?>" method="post" enctype="multipart/form-data">
            <table>
              <tr class="title"><td><?= __('Login') ?></td></tr>
              <tr><td class="td_login">
              <table class="noborder">
                <tr>
                  <td><?= __('Login') ?>:</td>
                  <td><input name="login" type="text" value="" /></td>
                </tr>
                <tr>
                  <td><?= __('Password') ?>:</td>
                  <td><input name="password" type="password" value="" /></td>
                </tr>
              </table>
              <br />
              <div>
                <input name="login_me" type="hidden" value="yes" />
                <input type="reset" />
                <input type="submit" />
              </div>
            </td></tr>
            </table>
            <div class="fred">
            <?php
            echo $session->showLoginStatus();
            ?>
            </div>
            </form>
            <script type="text/javascript">
            <!--
            if (document.getElementById("login_form").login.value == "")
            {
                    document.getElementById("login_form").login.focus()
            } else {
                    document.getElementById("login_form").password.focus()
            }
            //-->
            </script>
            <?php
            include SRC_DIR . '/includes/footer.html.inc.php';
            ?>
        </div>
	</body>
  <?php
}
?>
</html>
