<?php

define("LP_VERSION", "0.1");

require_once("config.php");
require_once("init.php");
require_once("html.php");
require_once("session.php");
require_once("nonce.php");

// FIXME: Provide unit-tests


/*
 * Processing starts here.
 */

// Configure stuff
$lp_config = lp_config();

// Do sanity checks
lp_init_check();

// Connect to DB
$db_conn = lp_db_pdo_init();

// Start session
lp_session_init();


// Implement caching control: No caching, at all.
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Deny framing this page 
header('X-Frame-Options: DENY');

// Check if presented redirect URI is registered with us
lp_redirect_uri_check();


/*
 * Check if all fields in login-form are in place, and if not,
 * show a login-form.
 */

if (
	(isset($_POST{"username"}) === FALSE) ||
	(empty($_POST{"username"})) ||
	(isset($_POST{"password"}) === FALSE) ||
	(empty($_POST{"password"})) ||
	(isset($_POST{"nonce"}) === FALSE) ||
	(empty($_POST{"nonce"}))
) {
	lp_login_form();
}


/*
 * If all fields are in place, try to verify user-input,
 * and then attempt to authenticate with the OAuth server.
 */

else if (
	(isset($_POST{"username"}) === TRUE) && 
	(isset($_POST{"password"}) === TRUE) && 
	(isset($_POST{"nonce"}) === TRUE)
) {

	/*
	 * Verify nonce-string provided. Please note that here we employ
	 * both a secret-key and a special session token; see lp_login_form()
	 * for more details.
	 */

	$nonceutil_check_success = lp_nonce_check(
		$lp_config["nonce_static_secret_key"],
		$_SESSION{"lp_nonce_session_secret"}, 
		$_POST{"nonce"}
	); 

	if ($nonceutil_check_success === FALSE) {
		lp_login_form("Something odd happend.");
	}

	else if ($nonceutil_check_success === TRUE) {

		/* 
		 * Use cURL to send a POST request to the OAuth server,
		 * asking to verify the given username and password in
		 * the request given to us, using the client credentials
		 * we were configured with.
		 *
		 */

		$oauth_req_curl_handle = curl_init($lp_config["oauth2_server_access_token_uri"]);

		$curl_req_body = json_encode(array(
			"grant_type"	=> "password",
			"username"	=> $_POST{"username"},
			"password"	=> $_POST{"password"},
			"client_id"	=> $lp_config["oauth2_client_id"],
			"client_secret"	=> $lp_config["oauth2_client_secret"],
			"scope"		=> "fleiss-api",
		));

		curl_setopt($oauth_req_curl_handle, CURLOPT_CUSTOMREQUEST, 	"POST");
		curl_setopt($oauth_req_curl_handle, CURLOPT_USERAGENT, 		"login-page/" . LP_VERSION);
		curl_setopt($oauth_req_curl_handle, CURLOPT_RETURNTRANSFER, 	TRUE);
		curl_setopt($oauth_req_curl_handle, CURLOPT_HEADER, 		0);   
		curl_setopt($oauth_req_curl_handle, CURLOPT_POST, 		TRUE);
		curl_setopt($oauth_req_curl_handle, CURLOPT_BINARYTRANSFER,	TRUE);

		curl_setopt($oauth_req_curl_handle, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($curl_req_body)
		));

		curl_setopt($oauth_req_curl_handle, CURLOPT_POSTFIELDS, 	$curl_req_body);

		$oauth_req_response_json = curl_exec($oauth_req_curl_handle);


		// Check if the request succeeded, if not, return an error to user.
		if ($oauth_req_response_json === FALSE) {
			lp_login_form("Unable to authenticate for some reason.");
		}


		// Get HTTP status code of our request
		$oauth_req_http_headers = curl_getinfo($oauth_req_curl_handle);

		if (
			($oauth_req_http_headers["http_code"] === 200) ||
			($oauth_req_http_headers["http_code"] === 401)
		) {
			$oauth_req_response_arr = json_decode($oauth_req_response_json);

			// If decoding of JSON does not succeed, we cannot do anything. */
			if ($oauth_req_response_arr === NULL) {
				lp_fatal_error("Could not decode response from OAuth server");
			}

			$oauth_req_response_arr = (array) $oauth_req_response_arr;
		}


		/*
		 * Now handle each HTTP status code separately
		 */		

		if ($oauth_req_http_headers["http_code"] === 200) {
			/*
			 * OK, decoding seems to have succeeded,
			 * start by converting the result into an array.
			 */

			$oauth_req_response_arr = (array) $oauth_req_response_arr;

			/*
			 * Now check if we actually got an access_token 
			 * to use. If so, redirect the browser to the redirect_uri specified
			 * in the request. 
			 * 
			 * Remember: This URI has already been verified to be registered
			 * with us, at the beginning of processing of this request.
			 */
			
			if (isset($oauth_req_response_arr["access_token"])) {
				header('HTTP/1.1 302 Found');
				header('Location: ' . urldecode($_REQUEST{"redirect_uri"}) . 
					'?access_token=' . $oauth_req_response_arr["access_token"]);
			}
			
			else {
				/*
				 * We did not get an access token, so we cannot continue. 
				 * Show user a login form.
				 */

				lp_login_form("Authentication failure.");
			}
		}

		else if ($oauth_req_http_headers["http_code"] === 401) {
			lp_login_form(
				(empty($oauth_req_response_arr["message"]) !== TRUE) ?
				$oauth_req_response_arr["message"] :
				"Wrong username or password?"
			);
		}

		else if ($oauth_req_http_headers["http_code"] === 500) {
			lp_login_form("Could not authenticate for some reason.");
		}

		else {
			lp_fatal_error("Unable to authenticate for an unknown reason.");
		}
	}

	else {
		lp_fatal_error("This should never happen.");
	}
}




