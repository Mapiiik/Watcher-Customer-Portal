<?php
######################################
# Common CLASS for access to system  #
######################################

// Variables
define ('A_DENIED',   0x00);
define ('A_GRANTED',  0x01);
define ('A_LOCKED',   0x02);
define ('A_BAD_IP',   0x03);
define ('A_UNKNOWN',  0x04);
define ('A_BLOCKED',  0x05);
define ('A_ERROR',    0x06);

// Primary class
class Session
{
	var $status;
	var $access;
	var $login;
	var $login_id;
	var $customer_id;
	// passed on by the CRM, not used by the portal yet
	var $rights;
	var $ip;
	// the CRM's token for the signed-in customer
	var $token;

	function __construct()
	{
		if(!isset($_SESSION['status'])) $_SESSION['status'] = TRUE;
		$this->status =& $_SESSION['status'];
		$this->access =& $_SESSION['access'];
		$this->login =& $_SESSION['login'];
		$this->login_id =& $_SESSION['login_id'];
		$this->customer_id =& $_SESSION['customer_id'];
		$this->rights =& $_SESSION['rights'];
		$this->ip =& $_SESSION['ip'];
		$this->token =& $_SESSION['token'];
	}

	// signs in with the login and password the customer typed, the CRM checks them
	function login($crm, $login, $password)
	{
		$this->access = A_DENIED;
		$this->login = FALSE;
		$this->login_id = FALSE;
		$this->customer_id = FALSE;
		$this->rights = FALSE;
		$this->ip = FALSE;
		$this->token = FALSE;

		$login = trim((string)$login);
		$password = (string)$password;
		if ($login === '' || $password === '') return $this->access;

		$answer = $crm->login($login, $password, $_SERVER['REMOTE_ADDR']);
		if ($answer === false)
		{
			$this->access = A_ERROR;
		}
		else if ($answer['status'] === 'granted')
		{
			// a new session identifier, so that one known before signing in is worth nothing after it
			session_regenerate_id(true);
			$this->access = A_GRANTED;
			$this->login = $answer['login']['login'];
			$this->login_id = $answer['login']['id'];
			$this->customer_id = $answer['customer']['id'];
			$this->rights = $answer['login']['rights'];
			$this->ip = $_SERVER['REMOTE_ADDR'];
			$this->token = $answer['token'];
		}
		else if ($answer['status'] === 'locked')
		{
			$this->access = A_LOCKED;
		}
		else if ($answer['status'] === 'blocked')
		{
			$this->access = A_BLOCKED;
		}
		return $this->access;
	}

	function setPassword($crm, $password)
	{
		return $crm->setPassword($this->token, (string)$password) !== false;
	}

	function isLogged()
	{
		$ret = FALSE;
		if ($this->login_id && $this->token)
		{
			if ($this->access === A_GRANTED)
			{
				$ret = TRUE;
                                if(filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                                    // none
                                }
                                else
                                {
                                    if (!($this->ip === $_SERVER['REMOTE_ADDR']))
                                    {
                                            $this->access = A_BAD_IP;
                                            $ret = FALSE;
                                    }
                                }
			}
		}
		return $ret;
	}

	function showLoginStatus()
	{
		// Access granted
		if($this->access === A_GRANTED)
		{
			return __('Access granted');
		}
		// Access denied
		if($this->access === A_DENIED)
		{
			return __('Access denied');
		}
		// Access locked
		if($this->access === A_LOCKED)
		{
			return __('Account locked');
		}
		// Bad IP
		if($this->access === A_BAD_IP)
		{
			return __('Your IP address has changed') . '<br />' . __('Security error');
		}
		// Too many failed attempts
		if($this->access === A_BLOCKED)
		{
			return __('Too many failed attempts, please try again in a while');
		}
		// The CRM did not answer
		if($this->access === A_ERROR)
		{
			return __('System error!');
		}
		return false;
	}

	function accessCheck()
	{
		if (! $this->isLogged())
		{
			$_SESSION['blockedURL'] = $_SERVER['REQUEST_URI'];
			?>
			<body class="center">
			<table class="center">
			  <tr><td>
				<a rel="toppage" href="<?php echo PREFIX; ?>/index.php" title="<?php echo __('You are not signed in, please sign in'); ?>"><?php echo __('You are not signed in, please sign in'); ?></a>
			  </td></tr>
			</table>
			<div class="fred">
			  <?php
			  echo $this->showLoginStatus();
			  ?>
			</div>
			</body>
			</html>
			<?php
			exit();
		}
	}

	// ends the page when the CRM did not answer, and signs the customer out when it says their
	// sign-in has expired
	function crmFailed($crm)
	{
		if ($crm->signedOut())
		{
			$this->logout();
			$this->accessCheck();
		}
		echo "<div class='fred'>" . __('System error!') . "</div>
</body>
</html>";
		exit();
	}

	function logout()
	{
		$this->access = FALSE;
		$this->login = FALSE;
		$this->login_id = FALSE;
		$this->customer_id = FALSE;
		$this->rights = FALSE;
		$this->ip = FALSE;
		$this->token = FALSE;
		session_destroy();
		//initialize new clear session
		session_start();
		return;
	}
}
?>