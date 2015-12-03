<?php

/*
 * Best kept here: Required by misc.php which is not always
 * invoked from index.php
 */

define("LP_VERSION", "0.1");

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

	if (
		(PHP_MAJOR_VERSION < 5) || 
		((PHP_MAJOR_VERSION == 5) && (PHP_MINOR_VERSION < 4))
	) {
		lp_fatal_error("Your version of PHP is out of date.");
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
			"login_form_heading",
			"login_form_error_prefix",
			"login_form_error_suffix",
			"nonce_static_secret_key",
			"oauth2_server_access_token_uri",
			"session_hashing_function",
			"session_secret_function",
			"session_entropy_length",
			"session_secret_function",
			"nonce_hashing_function",
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
	 * Check if all the hashing functions configured really are accessible...
	 */

	$hashing_function_keys = array(
		"session_hashing_function",
		"session_secret_function",
		"nonce_hashing_function",
	);

	foreach ($hashing_function_keys as $hashing_function_key_item) {
		if ((in_array($lp_config[$hashing_function_key_item], hash_algos(), TRUE)) === FALSE) {
			lp_fatal_error("This setup is not capable of generating random tokens (problematic function: " . $hashing_function_key_item . ").");
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

	/*
	 * Default session_start() function - to enable
	 * unit-tests to run with a different function.	
	 */

	$lp_config["session_start_func"] = 'session_start';


	/*
	 * Default time() function - to enable
	 * unit-tests to run with a different function.
	 */

	$lp_config["time_func"] = 'time';


	/*
	 * Default openssl_random_pseudo_bytes() - to enable
	 * unit-tests to run with a different function.
	 */

	$lp_config["openssl_random_pseudo_bytes_func"] = 'openssl_random_pseudo_bytes';
}


/*
 * LP_INI_SET:
 *
 * A simple wrapper around ini_set(),
 * but does a check of its return-value.
 */

function lp_ini_set($key, $value) {
	if (ini_set($key, $value) === FALSE) {
		lp_fatal_error("Unable to set PHP configuration option \"" . $key . "\"");
	}
}


/*
 * LP_TIME:
 *
 * A simple wrapper around time().
 *
 * This is to enable unit-tests to override
 * this function so that a static value can be
 * returned.
 *
 */

function lp_time() {
       	global $lp_config;

	if (($ret = (call_user_func($lp_config["time_func"]))) === FALSE) {
		lp_fatal_error("Could not get time!");
	}

	return $ret;
}


/*
 * LP_OPENSSL_RANDOM_PSEUDO_BYTES:
 *
 * A simple wrapper around openssl_random_pseudo_bytes().
 *
 * This is to enable unit-tests to override 
 * this function so that a static value can be
 * returned.
 */

function lp_openssl_random_pseudo_bytes($length, &$crypto_strong) {
       	global $lp_config;

	if (($ret = (call_user_func($lp_config["openssl_random_pseudo_bytes_func"], array($length, $crypto_strong)))) === FALSE) {
		lp_fatal_error("Could not get random bytes!");
	}

	return $ret;
}


/*
 * LP_DB_POD_INIT:
 *
 * Connect to configured db (in config.php) using
 * the PDO wrapper.
 *
 * Catches DB-connection errors if we are unable to
 * connect.
 */

function lp_db_pdo_init() {
	global $lp_config;

	/* 
	 * Connect to database using driver invocation 
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

		/*
		 * Make sure that when we encounter a DB error,
		 * don't throw exceptions, but rather return FALSE 
		 * from all function calls.
		 */

		$db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
	} 

	catch (PDOException $e) {
		lp_fatal_error("Could not connect to database: " . $e->getMessage());
	}

	return $db_conn;
}

/*
 * LP_HTTP_CURL_REQUEST:
 *
 * Send POST request to OAuth 2.0 server
 * with JSON in the body, using application/json as
 * content-type.
 * 
 * Returns response from the server.
 */

function lp_http_curl_request(&$curl_handle, $uri, $req_body_params_arr) {
	/* 
	 * Use cURL to send a POST request to the OAuth server,
	 * asking to verify the given username and password in
	 * the request given to us, using the client credentials
	 * we were configured with.
	 *
	 */

	$curl_handle = curl_init($uri);

	$curl_req_body = json_encode($req_body_params_arr);

	curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 	"POST");
	curl_setopt($curl_handle, CURLOPT_USERAGENT, 		"login-page/" . LP_VERSION);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 	TRUE);
	curl_setopt($curl_handle, CURLOPT_HEADER, 		0);
	curl_setopt($curl_handle, CURLOPT_POST, 		TRUE);
	curl_setopt($curl_handle, CURLOPT_BINARYTRANSFER,	TRUE);

	curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($curl_req_body)
	));

	curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $curl_req_body);

	$oauth_req_response_json = curl_exec($curl_handle);

	return $oauth_req_response_json;
}

/*
 * LP_SCOPE_INFO_GET:
 *
 * Get information about available scopes
 * from the OAuth 2.0 server.
 *
 * Sends a POST request with application/json as content-type,
 * expects reply to be JSON with information about scopes. 
 *
 * The reply might be in a format suchs as this:
 * { "my-api": "My API access", "profile": "Access to profile"}
 */
 
function lp_scope_info_get() {
	global $lp_config;

	$scopes_apc_key = 'lp_scopes_info';
	$apc_support = FALSE;


	/*
	 * Some systems might not have APC support.
	 * Deal with that.
	 */

	if (function_exists('apc_fetch') === TRUE) {
		$scopes_info = apc_fetch($scopes_apc_key);
		$apc_support = TRUE;
	}

	else {
		$apc_support = FALSE;
		$scopes_info = FALSE;
	}


	/*
	 * Check if scope-info was retreived from cache ...
	 */

	if ($scopes_info === FALSE) {
		/*
		 * Nothing found in cache - or cache
		 * not supported. Fetch from OAuth server.
	 	 */

		$scopes_info_req_json = lp_http_curl_request(
			$scopes_info_curl_handle, 
			$lp_config["oauth2_server_scopes_info_uri"], 
			array()
		);

		// Some error, return FALSE.
		if ($scopes_info_req_json === FALSE) {
			trigger_error("Could not fetch scope information");

			return FALSE;
		}

		/* 
		 * Do some diagnosis of the HTTP response code.
		 */

		$scopes_info_http_headers = curl_getinfo($scopes_info_curl_handle);

		if ($scopes_info_http_headers["http_code"] !== 200) {
			trigger_error("Server reported non-success when fetching scopes");

			return FALSE;
		}


		// Try to decode the JSON
		$scopes_info = json_decode($scopes_info_req_json);

		if ($scopes_info === NULL) {
			trigger_error("Could not decode JSON from server when fetching scopes");

			return FALSE;
		}

		if ($apc_support === TRUE) {
			apc_store($scopes_apc_key, $scopes_info, 60);
		}
	}
	
	$scopes_info = (array) $scopes_info;

	// Return what ever we got
	return $scopes_info;
}


