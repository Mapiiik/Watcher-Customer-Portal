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

$pageTitle = HTML_PAGE_NAME . ' - ' . CUSTOMERS_LIST;
require SRC_DIR . '/includes/head.html.inc.php';

// Init security
$session = new Session();
$session->accessCheck();

// Variables
$pageName = $_SERVER['SCRIPT_NAME'];
$sqllisting = false;

?>
<script type="text/javascript">
function deleteClick()
{
  if ( !confirm('<?php echo REALLY_DELETE; ?>') ) {
	return false;
  }
  else
	return true;
}
function confirmChange()
{
  if ( !confirm('<?php echo CONFIRM_CHANGE; ?>') ) {
	return false;
  }
  else
	return true;
}
function confirmSubmit()
{
  var checkBox = document.getElementById("gdpr");
  
  if ( !confirm('<?php echo CONFIRM_SUBMIT; ?>') ) {
    return false;
  }
  else
  {
    return true;
  }
}
function confirmSubmitHard()
{
  var checkBox = document.getElementById("gdpr");
  
  if (checkBox.checked == true)
  {
    if ( !confirm('<?php echo CONFIRM_SUBMIT; ?>') ) {
  	return false;
    }
    else
    {
	return true;
    }
  }
  else
  {
    alert('<?php echo PLEASE_CONFIRM_GDPR; ?>');
    return false;
  }
}
</script>

<body>
<?php
echo "<h1>" . CUSTOMER_DATA . "</h1>";
echo "<div>" . CUSTOMER_DATA_INFOTEXT . "</div>\n<br />\n";

////////////////
// PROCESSING //
////////////////
$crm = new Crm();
$answer = $crm->customer($session->token);
if ($answer === false) $session->crmFailed($crm);

$countries = $answer['countries'];
$customer = $answer['customer'];
// the contacts and addresses by their id, as the form names them
$customerEmails = array();
foreach ($customer['emails'] as $email) $customerEmails[$email['id']] = $email;
$customerPhones = array();
foreach ($customer['phones'] as $phone) $customerPhones[$phone['id']] = $phone;
$customerAddresses = array();
foreach ($customer['addresses'] as $address) $customerAddresses[$address['id']] = $address;
$customer['date_of_birth'] = formatDate($customer['date_of_birth']);
$customer['ic'] = $customer['identity_number'];
$customer['dic'] = $customer['vat_number'];
$customers[$customer['id']] = $customer;

{
		//START edit item
		$edit_id = $customer['id'];

		if (isset($_POST['agree_gdpr']) && ($_POST['agree_gdpr'] == 'yes'))
		{
                        // START update agreements, the CRM labels the GDPR consent
                        echo DB_SNP . "...";
                        $consents = array();
                        foreach (array('agree_gdpr', 'agree_mailing_billing', 'agree_mailing_outages', 'agree_mailing_commercial') as $consent)
                        {
                                $consents[$consent] = (int)(isset($_POST[$consent]) && $_POST[$consent] == 'yes');
                        }
                        if ($crm->consents($session->token, $consents))
                                echo " OK" . "<br />\r\n";
                        else
                                $session->crmFailed($crm);
                        //END update agreements
                        
                        echo "<br />\r\n";
                        
			$mailbody = "UPDATE DATA :) \nRequest from: {$session->login} [{$session->login_id}]\n\n";
			foreach ($_POST as $key => $value)
			{
                                if ($key == 'address')
                                {
                                        $addresses = $customerAddresses;
                                        foreach ($addresses as $addresskey => $address)
                                        {
                                            $addresses[$addresskey]['active'] = 'yes';
                                            unset($addresses[$addresskey]['id']);
                                            unset($addresses[$addresskey]['type']);

                                            // remove address records without changes
                                            if (isset($value[$addresskey]) && $addresses[$addresskey] == $value[$addresskey]) {
                                                unset($addresses[$addresskey]);
                                                unset($value[$addresskey]);
                                            }
                                        }

                                        if ($addresses <> $value)
                                        {
                                            $mailbody .= sprintf("[%-15s] ",   "$key:") . print_r($value, TRUE) . "\n";
                                        }
                                        unset($addresses);
                                }
                                else if ($key == 'email')
                                {
                                        $emails = $customerEmails;
                                        if ($value['new']['email'] == '') unset($value['new']);
                                        foreach ($emails as $emailkey => $email)
                                        {
                                            unset($emails[$emailkey]['id']);
                                            foreach ($email as $keyx => $valuex)
                                            {
                                                if ($valuex === true) $emails[$emailkey][$keyx] = 'yes';
                                                if ($valuex === false) unset($emails[$emailkey][$keyx]);
                                            }
                                        }

                                        if ($emails <> $value)
                                        {
                                            $mailbody .= sprintf("[%-15s] ",   "$key:") . print_r($value, TRUE) . "\n";
                                        }
                                        unset($emails);
                                }
                                else if ($key == 'phone')
                                {
                                        $phones = $customerPhones;
                                        if ($value['new']['phone'] == '') unset($value['new']);
                                        foreach ($phones as $phonekey => $phone) unset($phones[$phonekey]['id']);
                                        
                                        if ($phones <> $value)
                                        {
                                            $mailbody .= sprintf("[%-15s] ",   "$key:") . print_r($value, TRUE) . "\n";
                                        }
                                        unset($phones);
                                }
				else if (!isset($customers[$edit_id][$key]) || $customers[$edit_id][$key] <> $value)
                                {
					$mailbody .= sprintf("[%-15s] ",   "$key:") . str_replace(array("\n"), array("\n                  "), $value) . "\n";
                                }
			}
                        
                        $mailbody .= "\n";
                        $mailbody .= "Timestamp:\t" . formatDateTime(time()) . "\n";
                        $mailbody .= "IP address:\t" . $_SERVER['REMOTE_ADDR'] . "\n";
			if (sendMessage($mailbody, CHANGE_REQUESTS_TO, "Customer-data change requested - " . $customers[$edit_id]['number'])) echo DATA_SENT_TO_PROCESSING . "<br /><br />";
			//echo $mailbody;
		}
		else
		{
			// START Customer informations
			echo "\n\n" . '<form action="' . "$pageName?edit=$edit_id" . '" method="post" enctype="multipart/form-data" name="update_data" lang="' . LANGUAGE . '">';
                        require profilePart('consent');
                        ?>

                        <?php /* <p><input id="gdpr" name="agree_gdpr" type="checkbox" <?php if ($customers[$edit_id]["agree_gdpr"]) echo ' checked="checked"'; ?> value="yes"><strong><?php echo CONFIRM_GDPR . '</strong><br /> (' . PLEASE_CONFIRM_GDPR . ')'; ?></p> */ ?>
                        <input id="gdpr" name="agree_gdpr" type="hidden" value="yes">

                        <p><input name="agree_mailing_billing" type="checkbox" <?php if ($customers[$edit_id]["agree_mailing_billing"]) echo ' checked="checked"'; ?> value="yes"><strong><?php echo CONFIRM_MAILING_BILLING; ?></strong></p>
                        <p><input name="agree_mailing_outages" type="checkbox" <?php if ($customers[$edit_id]["agree_mailing_outages"]) echo ' checked="checked"'; ?> value="yes"><strong><?php echo CONFIRM_MAILING_OUTAGES; ?></strong></p>
                        <p><input name="agree_mailing_commercial" type="checkbox" <?php if ($customers[$edit_id]["agree_mailing_commercial"]) echo ' checked="checked"'; ?> value="yes"><strong><?php echo CONFIRM_MAILING_COMMERCIAL; ?></strong></p>
    
                        <?php
                        echo "<br />";
                        
		// First column
		echo "<br /></br>\n<table class='outline'>";
		echo "\n<tr><td>";
		
		// USER
//  		error_reporting(E_ERROR); // need skip notices about unset variables if adding customer
		echo "\n\n <strong>" . USER_INFORMATIONS . "...</strong>";
		echo "\n <table class='border'>";
		// id
		if (! $edit_id == 0)
		{
                    echo "\n  <tr>";
                    echo "\n   <td width='100'>ID:</td>";
                    echo "\n   <td>" . '<input name="id" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["id"]) . '" readonly="readonly">' . "</td>";
                    echo "\n  </tr>";
                    echo "\n  <tr>";
                    echo "\n   <td width='100'>" . VARSYM . ":</td>";
                    echo "\n   <td>" . '<input name="varsym" type="text" value="' . $customers[$edit_id]['number'] . '" readonly="readonly">' . "</td>";
                    echo "\n  </tr>";
		}
                    else $customers[$edit_id]["invoice_delivery_type"] = 1;
		// company
		echo "\n  <tr>";
		echo "\n   <td>" . COMPANY . ":</td>";
		echo "\n   <td>" . '<input name="company" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["company"]) . '">' . "</td>";
		echo "\n  </tr>";
		// title
		echo "\n  <tr>";
		echo "\n   <td>" . TITLE . ":</td>";
		echo "\n   <td>" . '<input name="title" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["title"]) . '" maxlength="16">' . "</td>";
		echo "\n  </tr>";
		// last name
		echo "\n  <tr>";
		echo "\n   <td>" . NAME_LAST . ":</td>";
		echo "\n   <td>" . '<input name="last_name" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["last_name"]) . '">' . "</td>";
		echo "\n  </tr>";
		// first name
		echo "\n  <tr>";
		echo "\n   <td>" . NAME . ":</td>";
		echo "\n   <td>" . '<input name="first_name" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["first_name"]) . '">' . "</td>";
		echo "\n  </tr>";
		// suffix
		echo "\n  <tr>";
		echo "\n   <td>" . SUFFIX . ":</td>";
		echo "\n   <td>" . '<input name="suffix" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["suffix"]) . '">' . "</td>";
		echo "\n  </tr>";
		// date_of_birth
		echo "\n  <tr>";
		echo "\n   <td>" . DATE_OF_BIRTH . ":</td>";
		echo "\n   <td>" . '<input name="date_of_birth" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["date_of_birth"]) . '">' . "</td>";
		echo "\n  </tr>";
		// identity_card_number
		echo "\n  <tr>";
		echo "\n   <td>" . IDENTITY_CARD_NUMBER . ":</td>";
		echo "\n   <td>" . '<input name="identity_card_number" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["identity_card_number"]) . '">' . "</td>";
		echo "\n  </tr>";
                if (! $edit_id == 0)
		{
                        // e-mail
                        echo "\n  <tr>";
                        echo "\n   <td>" . EMAIL . ":</td>";
                        echo "\n   <td>";

                        $emails = $customerEmails;
                        // add empty email field
                        $emails['new']['id'] = 'new';
                        $emails['new']['email'] = '';
                        $emails['new']['use_for_billing'] = true;
                        $emails['new']['use_for_outages'] = true;
                        $emails['new']['use_for_commercial'] = true;

                        foreach ($emails as $email)
                        {
                            echo '<input name="email[' . $email["id"] . '][email]" type="text" value="' . htmlspecialchars((string)$email["email"]) . '">';
                            echo '<input name="email[' . $email["id"] . '][use_for_billing]" type="checkbox" title="' . USE_FOR_BILLING . '" value="yes"';
                            if ($email["use_for_billing"]) echo " checked";
                            echo '>B';
                            echo '<input name="email[' . $email["id"] . '][use_for_outages]" type="checkbox" title="' . USE_FOR_OUTAGES . '" value="yes"';
                            if ($email["use_for_outages"]) echo " checked";
                            echo '>O';
                            echo '<input name="email[' . $email["id"] . '][use_for_commercial]" type="checkbox" title="' . USE_FOR_COMMERCIAL . '" value="yes"';
                            if ($email["use_for_commercial"]) echo " checked";
                            echo '>C';
                            echo "<br />";
                        }
                        echo "</td>";
                        echo "\n  </tr>";
                        // phone
                        echo "\n  <tr>";
                        echo "\n   <td>" . PHONE . ":</td>";
                        echo "\n   <td>";
                        $phones = $customerPhones;
                        // add empty phone field
                        $phones['new']['id'] = 'new';
                        $phones['new']['phone'] = '';
                        foreach ($phones as $phone) echo '<input name="phone[' . $phone["id"] . '][phone]" type="text" value="' . htmlspecialchars((string)$phone["phone"]) . '">' . "<br />";
                        echo "</td>";
                        echo "\n  </tr>";
                        // www
                        echo "\n  <tr>";
                        echo "\n   <td>" . WWW . ":</td>";
                        echo "\n   <td>" . '<input name="www" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["www"]) . '">' . "</td>";
                        echo "\n  </tr>";
                }
                echo "\n </table>";

		// Second column
		echo "\n</td><td width='60'></td><td>";

		// BILLING INFORMATIONS
		echo "\n\n <strong>" . BILLING_INFORMATIONS . "...</strong>";
		echo "\n <table class='border'>";
		// Company ID
		echo "\n  <tr>";
		echo "\n   <td width='100'>" . IC . ":</td>";
		echo "\n   <td>" . '<input name="ic" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["ic"]) . '">' . "</td>";
		echo "\n  </tr>";
		// VAT number
		echo "\n  <tr>";
		echo "\n   <td>" . DIC . ":</td>";
		echo "\n   <td>" . '<input name="dic" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["dic"]) . '">' . "</td>";
		echo "\n  </tr>";
		echo "\n </table>";

                if (! $edit_id == 0)
		{
                    // BANK INFORMATIONS
                    echo "\n\n <strong>" . BANK_INFORMATIONS . "...</strong>";
                    echo "\n <table class='border'>";
                    // bank name
                    echo "\n  <tr>";
                    echo "\n   <td width='100'>" . BANK_NAME . ":</td>";
                    echo "\n   <td>" . '<input name="bank_name" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["bank_name"]) . '">' . "</td>";
                    echo "\n  </tr>";
                    // bank code
                    echo "\n  <tr>";
                    echo "\n   <td>" . BANK_CODE . ":</td>";
                    echo "\n   <td>" . '<input name="bank_code" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["bank_code"]) . '" maxlength="4">' . "</td>";
                    echo "\n  </tr>";
                    // bank account
                    echo "\n  <tr>";
                    echo "\n   <td>" . BANK_ACCOUNT . ":</td>";
                    echo "\n   <td>" . '<input name="bank_account" type="text" value="' . htmlspecialchars((string)$customers[$edit_id]["bank_account"]) . '">' . "</td>";
                    echo "\n  </tr>";
                    echo "\n </table>";
                }

                // ADDITIONAL SETTINGS
		echo "\n\n <strong>" . ADDITIONAL_SETTINGS . "...</strong>";
		echo "\n <table class='border'>";
		// invoice delivery
		echo "\n  <tr>";
		echo "\n   <td width='100'>" . INVOICE_DELIVERY . ":</td>";
		echo "\n   <td>" . '<select name="invoice_delivery_type">';
		foreach($invoice_delivery_type_options as $invoice_delivery_typeId => $invoice_delivery_typeName)
		{
			echo "\n    <option value=\"$invoice_delivery_typeId\"";
			if($invoice_delivery_typeId == $customers[$edit_id]["invoice_delivery_type"]) echo ' selected="selected"';
			echo ">$invoice_delivery_typeName</option>";
		}
		echo "\n   </select></td>";
		echo "\n  </tr>";
		echo "\n </table>";

		// end table
		echo "\n</td></tr>";
		echo "\n</table>";

            $addresses = $customerAddresses;

            foreach ($addresses as $address)
            {
                // ADDRESS
                if (isset($address['type']))
                {
                    echo "\n\n " . '<input name="address[' . $address["id"] . '][active]" type="checkbox" checked value="yes" onClick="return deleteClick()">';
                    echo "<strong>" . $address_types[$address['type']] . "...</strong>";
                    echo " (<a rel='external' href='http://maps.google.com/maps?q=" . htmlspecialchars((string)$address["street"] . " " . $address["number"] . ", " . $address["city"] . ", " . $countries[$address["country_id"]]) . "'" . ' title="' . SHOW_ON_MAP . '">' . SHOW_ON_MAP . "</a>)";
                }
                else
                {
                    echo "\n\n " . '<input name="address[' . $address["id"] . '][active]" type="checkbox" value="yes">';
                    echo '<select name="address[' . $address["id"] . '][type]">';
                    foreach($address_types as $addressTypeId => $addressType)
                    {
                            echo "<option value=\"$addressTypeId\">$addressType</option>";
                    }
                    echo "</select>";
                }
                echo "\n <table class='border'>";
                // company
                echo "\n  <tr>";
                echo "\n   <td>" . COMPANY . ":</td>";
                echo "\n   <td>" . '<input name="address[' . $address["id"] . '][company]" type="text" value="' . htmlspecialchars((string)$address["company"]) . '">' . "</td>";
                // street
                echo "\n   <td width='100'>" . STREET . ":</td>";
                echo "\n   <td>" . '<input name="address[' . $address["id"] . '][street]" type="text" value="' . htmlspecialchars((string)$address["street"]) . '">' . "</td>";
                echo "\n  </tr>";
                // title
                echo "\n  <tr>";
                echo "\n   <td>" . TITLE . ":</td>";
                echo "\n   <td>" . '<input name="address[' . $address["id"] . '][title]" type="text" value="' . htmlspecialchars((string)$address["title"]) . '" maxlength="16">' . "</td>";
                // number
                echo "\n   <td>" . NUMBER . ":</td>";
                echo "\n   <td>" . '<input name="address[' . $address["id"] . '][number]" type="text" value="' . htmlspecialchars((string)$address["number"]) . '" maxlength="16">' . "</td>";
                echo "\n  </tr>";
                // last name
                echo "\n  <tr>";
                echo "\n   <td>" . NAME_LAST . ":</td>";
                echo "\n   <td>" . '<input name="address[' . $address["id"] . '][last_name]" type="text" value="' . htmlspecialchars((string)$address["last_name"]) . '">' . "</td>";
                // city
                echo "\n   <td>" . CITY . ":</td>";
                echo "\n   <td>" . '<input name="address[' . $address["id"] . '][city]" type="text" value="' . htmlspecialchars((string)$address["city"]) . '">' . "</td>";
                echo "\n  </tr>";
                // first name
                echo "\n  <tr>";
                echo "\n   <td>" . NAME . ":</td>";
                echo "\n   <td>" . '<input name="address[' . $address["id"] . '][first_name]" type="text" value="' . htmlspecialchars((string)$address["first_name"]) . '">' . "</td>";
                // zip
                echo "\n   <td>" . ZIP . ":</td>";
                echo "\n   <td>" . '<input name="address[' . $address["id"] . '][zip]" type="text" value="' . htmlspecialchars((string)$address["zip"]) . '" maxlength="5">' . "</td>";
                echo "\n  </tr>";
                // suffix
                echo "\n  <tr>";
                echo "\n   <td>" . SUFFIX . ":</td>";
                echo "\n   <td>" . '<input name="address[' . $address["id"] . '][suffix]" type="text" value="' . htmlspecialchars((string)$address["suffix"]) . '">' . "</td>";
                // country
                echo "\n   <td>" . COUNTRY . ":</td>";
                echo "\n   <td>" . '<select name="address[' . $address["id"] . '][country_id]">';
                foreach($countries as $country_id => $country_name)
                {
                        echo "\n    <option value=\"$country_id\"";
                        if($country_id == $address["country_id"]) echo ' selected="selected"';
                        echo ">$country_name</option>";
                }
                echo "\n   </select></td>";
                echo "\n  </tr>";
                echo "\n </table>";
                }

                // IPs
                echo "\n<br /><strong>" . IP_ADDRESS . '...</strong><br />';
                echo "<table class='normal_table'>";
                echo "<tr class='title'><th>" . IP_ADDRESS . "</th><th>" . CONTRACT . "</th><th>OKU</th></tr>";
                foreach($customer['ip_addresses'] as $ip_address)
                {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($ip_address['ip_address']) . "</td>";
                        echo "<td>" . htmlspecialchars((string)$ip_address['contract_number']) . "</td>";
                        echo "<td>" . htmlspecialchars((string)$ip_address['subscriber_verification_code']) . "</td>";
                        echo "</tr>";
                }
                echo "\n </table>";

                
                // buttons
                echo "\n<br />";
                echo "\n <div class='outline'><table>";
                echo "\n  <tr align='center'>";
                echo "\n   <td width='150'>" . '<input type="reset"></td>';
                echo "\n   <td width='150'>" . '<input type="submit" onclick="return confirmSubmit();"></td>';
                echo "\n  </tr>";
                echo "\n </table></div>";
                echo "\n</form>";
            }

		// Reporting back to normal
		if (DEBUG) {
		  error_reporting(E_ALL);
		} else {
		  error_reporting(E_ERROR);
		}
		// END Customer informations

		//END edit item
}
?>
</body>
</html>
