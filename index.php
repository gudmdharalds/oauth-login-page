<?php

define("LP_VERSION", "0.1");

# FIXME: Rate-limiting functionality 

require_once("config.php");
require_once("html.php");
require_once("session.php");

# FIXME: NonceUtil - fork, use sha256 
require_once("NonceUtil.php");


/*
 * LP_INIT_CHECK:
 *
 * Check if configuration settings is sane.
 */

function lp_init_check() {
	global $lp_config;

	/*
	 * Check if the version of PHP is recent enough.
	 */

	if ((PHP_MAJOR_VERSION < 5) || (PHP_MINOR_VERSION < 4)) {
		lp_fatal_error("Your version of PHP is out of date.");
	}


	/*
	 * Check if we have access to the configured session token
	 * hashing function.
	 */

	if ((in_array($lp_config["session_token_function"], hash_algos(), TRUE)) === FALSE) {
		lp_fatal_error("This setup is not capable of generating random tokens.");
	}


	/*
	 * Check if required settings are configured.
	 * Report a fatal error if not.
	 */

	foreach (array(
			"image_page",
			"image_icon",
			"page_title_prefix",
			"css_file",
			"nonce_secret_key",
			"oauth2_server_access_token_uri",
			"oauth2_client_id",
			"oauth2_client_secret",
			"valid_redirect_uris",
			"session_hashing_function",
			"session_entropy_length",
			"session_token_function",
			"db_driver",
			"db_name",
			"db_host",
			"db_user",
			"db_pass",
		) as $check_config_key) {

		if (
			(isset($lp_config[$check_config_key]) === FALSE) || 
			(empty($lp_config[$check_config_key]) === TRUE)
		) {
			lp_fatal_error("Incorrectly configured. Missing setting: \"" . $check_config_key . "\"");
		}
	}


	/*
	 * Check if we can access all the template files.
	 */

	if (
		(stat("tpl/header.tpl.php") === FALSE) ||
		(stat("tpl/footer.tpl.php") === FALSE) ||
		(stat("tpl/login-form.tpl.php") === FALSE) ||
		(stat("tpl/error.tpl.php") === FALSE)
	) {
		lp_fatal_error("Could not open template file");
	}
}

/*
 *
 * LP_REDIRECT_URI_CHECK:
 *
 * Check if submitted redirect URI is found in our settings.
 * If so, it is safe to redirect calling user-agents to the URI.
 *
 * Returns:
 *	Nothing
 *
 * Side-effects:
 *	Might stop running of the script if URI is not found.
 */

function lp_redirect_uri_check() {
	global $lp_config;

	if (in_array(urldecode(@$_POST{"redirect_uri"}), $lp_config["valid_redirect_uris"], TRUE) === FALSE) {
		lp_fatal_error("Invalid redirection URI");
	}
}

function lp_db_pdo_init() {
	global $lp_config;

	/* 
	 * Connect to an ODBC database using driver invocation 
	 */

	try {
		$db_conn = new PDO(
					$lp_config["db_driver"] . ':' .
						'dbname=' . $lp_config["db_name"] . ';' .
						'host=' . $lp_config["db_host"] . ';' .
						'charset=UTF8',
					$lp_config["db_user"], 
					$lp_config["db_pass"]
				);
	} 

	catch (PDOException $e) {
		lp_fatal_error("Could not connect to database: " . $e->getMessage());
	}

	return $db_conn;
}
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
 * Check if user has any session token attached to his session.
 * If not, generate one and attach. See lp_login_form()
 * for a detailed description of why this is necessary.
 */

if (isset($_SESSION{"lp_session_token"}) === FALSE) {
	$lp_session_token = lp_generate_session_token();

	if ($lp_session_token === FALSE) {
		lp_fatal_error("Cannot continue; the system is not correctly configured.");
	}

	$_SESSION{"lp_session_token"} = $lp_session_token;

	session_write_close();
}


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

	$nonceutil_check_success = NonceUtil::check(
		$lp_config["nonce_secret_key"] . '-' . $_SESSION{"lp_session_token"}, 
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
		
		$oauth_req_http_headers = curl_getinfo($oauth_req_curl_handle);

		if ($oauth_req_http_headers["http_code"] === 200) {
			$oauth_req_response_arr = json_decode($oauth_req_response_json);

			// If decoding of JSON does not succeed, we cannot do anything. */
			if ($oauth_req_response_arr === NULL) {
				lp_fatal_error("Could not decode response from OAuth server");
			}

			else {
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
					header('Location: ' . urldecode($_REQUEST{"redirect_uri"}) . '?access_token=' . $oauth_req_response_arr["access_token"]);
				}
			
				else {
					/*
					 * We did not get an access token, so we cannot continue. 
					 * Show user a login form.
					 */

					lp_login_form("Authentication failure.");
				}
			}
		}

		else if ($oauth_req_http_headers["http_code"] === 401) {
			lp_login_form("Wrong username or password?");
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




