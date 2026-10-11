<?php
require dirname(__DIR__) . '/src/bootstrap.php';



session_start();
header("Pragma: no-cache");
header("Content-type: text/html; charset=UTF-8");
// iframe security
header("Content-Security-Policy: frame-ancestors 'self'");
header("X-Frame-Options: SAMEORIGIN");

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
     <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo LANGUAGE; ?>" lang="<?php echo LANGUAGE; ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="author" content="Martin Patočka [www.mapik.net]" />
	<meta name="description" content="<?php echo htmlspecialchars(HTML_PAGE_NAME); ?>" />
	<meta name="robots" content="noindex, nofollow" />

	<title><?php echo htmlspecialchars($pageTitle); ?></title>
	<link href="<?php echo PREFIX; ?>/style/portal.css" rel="stylesheet" type="text/css" />
</head>
<frameset cols="200,*">
  <frame frameborder="0" framespacing="0" src="<?php echo PREFIX; ?>/menu.php" name="_menu" scrolling="no" />
<?php
if (isset($_SESSION['blockedURL']) && ($_SESSION['blockedURL'] <> '')) {
?>
  <frame frameborder="0" framespacing="0" src="<?php echo htmlspecialchars($_SESSION['blockedURL']); ?>" name="_right" scrolling="auto" />
<?php
    unset($_SESSION['blockedURL']);
} else { ?>
  <frame frameborder="0" framespacing="0" src="<?php echo PREFIX; ?>/introduction.php/<?php echo $year?>/<?php echo $month; ?>/" name="_right" scrolling="auto" />
<?php } ?>
  <noframes>
    <body><p>Na této stránce jsou použity rámce, prohlížeč je však nepodporuje.</p></body>
  </noframes>
</frameset>
<?php
}
else
{
  require SRC_DIR . '/includes/head.html.inc.php';
	?>
	<body>
	<p><?php echo profileImage($profile['logo']); ?></p>
        <div class="center">
            <h1><?php echo htmlspecialchars($profile['heading']); ?></h1>
            <form id="login_form" action="<?php echo $pageName; ?>" method="post" enctype="multipart/form-data">
            <table>
              <tr class="title"><td><?php echo LOGIN; ?></td></tr>
              <tr><td class="td_login">
              <table class="noborder">
                <tr>
                  <td><?php echo LOGIN; ?>:</td>
                  <td><input name="login" type="text" value="" /></td>
                </tr>
                <tr>
                  <td><?php echo PASSWORD; ?>:</td>
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
