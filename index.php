<?php

define("LP_VERSION", "0.1");

require_once("config.php");

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


/*
 * Check if the calling site has provided us with 
 * required fields.
 */

if (
	(isset($_REQUEST{"response_type"}) === FALSE) ||
	(empty($_REQUEST{"response_type"})) ||

	($_REQUEST{"response_type"} !== "token") ||

	(isset($_REQUEST{"client_id"}) === FALSE) ||
	(empty($_REQUEST{"client_id"})) ||

	(isset($_REQUEST{"redirect_uri"}) === FALSE) ||
	(empty($_REQUEST{"redirect_uri"})) ||

	(isset($_REQUEST{"scope"}) === FALSE) ||
	(empty($_REQUEST{"scope"})) ||

	(isset($_REQUEST{"state"}) === FALSE) ||
	(empty($_REQUEST{"state"}))
) {

	lp_fatal_error("Invalid client settings");
}


/*
 * Check if all fields in login-form are in place, and if not,
 * show a login-form.
 */

else if (
	(isset($_REQUEST{"username"}) === FALSE) ||
	(empty($_REQUEST{"username"})) ||

	(isset($_REQUEST{"password"}) === FALSE) ||
	(empty($_REQUEST{"password"})) ||

	(isset($_REQUEST{"nonce"}) === FALSE) ||
	(empty($_REQUEST{"nonce"})) 

) {

	lp_login_form();
}


/*
 * If all fields are in place, try to verify user-input,
 * and then attempt to authenticate with the OAuth server.
 */

else if (
	(isset($_REQUEST{"username"}) === TRUE) && 
	(isset($_REQUEST{"password"}) === TRUE) && 
	(isset($_REQUEST{"nonce"}) === TRUE) && 
	(isset($_REQUEST{"response_type"}) === TRUE) && 
	($_REQUEST{"response_type"} == "token") &&
 	(isset($_REQUEST{"client_id"}) === TRUE) && 
	(isset($_REQUEST{"redirect_uri"}) === TRUE) && 
	(isset($_REQUEST{"scope"}) === TRUE) && 
	(isset($_REQUEST{"state"}) === TRUE)
) {

	/*
	 * Verify nonce-string provided. Please note that here we employ
	 * both a secret-key and a special session token; see lp_login_form()
	 * for more details.
	 */

	$nonceutil_check_success = lp_nonce_check(
		$lp_config["nonce_static_secret_key"],
		$_SESSION{"lp_nonce_session_secret"}, 
		$_REQUEST{"nonce"}
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

		$curl_req_body_arr = array(
			"grant_type"	=> $lp_config["oauth2_grant_type"],
			"username"	=> $_REQUEST{"username"},
			"password"	=> $_REQUEST{"password"},
			"client_id"	=> $_REQUEST{"client_id"}, 
			"client_secret"	=> "",
			"scope"		=> $_REQUEST{"scope"},	
			"redirect_uri"	=> urldecode($_REQUEST{"redirect_uri"}), 
		);


		$oauth_req_response_json = lp_http_curl_request(
			$oauth_req_curl_handle, 
			$lp_config["oauth2_server_access_token_uri"], 
			$curl_req_body_arr
		);


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
			 * with the OAuth server and belonging to the client credentials used.
			 * Thus it should be safe to redirect to this URI.
			 */

			if (isset($oauth_req_response_arr["access_token"])) {
				header('HTTP/1.1 302 Found');

				$header_location_str =	
						'Location: ' . urldecode($_REQUEST{"redirect_uri"}) . 
						'#access_token=' . $oauth_req_response_arr["access_token"] .
						'&token_type=' . $oauth_req_response_arr["token_type"] .
						'&expires_in=' . $oauth_req_response_arr["expires_in"];

				if (
					(isset($oauth_req_response_arr["scope"])) && 
					(empty($oauth_req_response_arr["scope"]) === FALSE)
				) {
					$header_str .= '&scope=' . $oauth_req_response_arr["scope"];
				}

				if (
					(isset($_REQUEST{"state"})) &&
					(empty($oauth_req_response_arr["state"]) === FALSE)
				) {
					$header_str .= '&state=' . $oauth_req_response_arr["state"];
				}

				// Send the header
				header($header_location_str);
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




