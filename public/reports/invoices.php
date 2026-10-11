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

if (!isset($_GET['invoice']))
{
    header("Content-type: text/html; charset=UTF-8");
    header("Pragma: no-cache");
    // iframe security
    header("Content-Security-Policy: frame-ancestors 'self'");
    header("X-Frame-Options: SAMEORIGIN");
} else {
    header('Pragma: public');
}

$pageTitle = HTML_PAGE_NAME . ' - ' . INVOICES_LIST;
if (!isset($_GET['invoice'])) require SRC_DIR . '/includes/head.html.inc.php';

// Init security
$session = new Session();
$session->accessCheck();

// Variables
$pageName = $_SERVER['SCRIPT_NAME'];
$sqllisting = false;

////////////////
// PROCESSING //
////////////////
$crm = new Crm();
$answer = $crm->invoices($session->token);
if ($answer === false) $session->crmFailed($crm);
$invoices = $answer['invoices'];

if (isset($_GET['invoice']))
{
    $invoice = null;
    foreach ($invoices as $candidate)
    {
        if ($candidate['id'] === $_GET['invoice']) $invoice = $candidate;
    }

    if (isset($invoice))
    {
        if ($file = $crm->invoicePdf($session->token, $invoice['id']))
        {
            if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'],'MSIE'))
                    header('Content-Type: application/force-download');
            else
                    header('Content-Type: application/octet-stream');

            $filename = strtr($profile['invoice_file'], ['{number}' => $invoice['number'], '{variable_symbol}' => $invoice['variable_symbol']]);
            header('Content-Disposition: attachment; filename="' . str_replace(['"', '/', '\\'], '-', $filename) . '"');
            echo($file);
        } else echo FILE_NOT_FOUND;
    } else echo NOT_FOUND;
}
else
{
	echo "<body>
";
	printInvoicesTable($invoices);
	echo "</body></html>";
}
?>