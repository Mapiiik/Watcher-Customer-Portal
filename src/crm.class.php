<?php
######################################
# Watcher CRM, asked over its API    #
######################################

// The portal signs in to the CRM as a user of its own (CRM_API_KEY). A customer signs in through
// login(), and the token that returns, kept in the session, limits every other answer to that
// customer. Needs nothing but allow_url_fopen.
class Crm
{
	// HTTP status of the last answer, 0 when the CRM did not answer at all
	var $status = 0;

	function login($login, $password, $ip)
	{
		return $this->json('POST', '/login', null, array('login' => $login, 'password' => $password, 'ip' => $ip));
	}

	function customer($token)
	{
		return $this->json('GET', '/customer', $token);
	}

	function consents($token, $consents)
	{
		return $this->json('POST', '/consents', $token, $consents);
	}

	function setPassword($token, $password)
	{
		return $this->json('POST', '/password', $token, array('password' => $password));
	}

	function invoices($token)
	{
		return $this->json('GET', '/invoices', $token);
	}

	// the PDF of one of the customer's invoices, or false
	function invoicePdf($token, $id)
	{
		$body = $this->request('GET', '/invoices/' . rawurlencode($id) . '/download', $token);
		return $this->status == 200 ? $body : false;
	}

	// whether the last answer said the customer is no longer signed in
	function signedOut()
	{
		return $this->status == 401;
	}

	// the decoded answer, or false when there is none
	function json($method, $path, $token = null, $data = null)
	{
		$body = $this->request($method, $path, $token, $data, 'application/json');
		if ($this->status != 200) return false;
		$answer = json_decode((string)$body, true);
		return is_array($answer) ? $answer : false;
	}

	function request($method, $path, $token = null, $data = null, $accept = '*/*')
	{
		$headers = array('Authorization: Bearer ' . CRM_API_KEY, 'Accept: ' . $accept);
		if ($token !== null) $headers[] = 'X-Customer-Token: ' . $token;
		$http = array('method' => $method, 'ignore_errors' => true, 'timeout' => 20, 'follow_location' => 0);
		if ($data !== null)
		{
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
			$http['content'] = http_build_query($data);
		}
		$http['header'] = implode("\r\n", $headers);
		$ssl = array('verify_peer' => CRM_API_VERIFY_TLS, 'verify_peer_name' => CRM_API_VERIFY_TLS);

		$this->status = 0;
		$body = @file_get_contents(CRM_API_URL . $path, false, stream_context_create(array('http' => $http, 'ssl' => $ssl)));
		$response = http_get_last_response_headers();
		if (isset($response[0]) && preg_match('#^HTTP/\S+\s+(\d{3})#', $response[0], $match)) $this->status = (int)$match[1];
		return $body;
	}
}
?>
