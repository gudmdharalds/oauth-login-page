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
			"db_dsn",
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
 * this function so that a custom value can be
 * returned.
 *
 */

function lp_time() {
	return time();
}


/*
 * LP_OPENSSL_RANDOM_PSEUDO_BYTES:
 *
 * A simple wrapper around openssl_random_pseudo_bytes().
 *
 * This is to enable unit-tests to override 
 * this function so that a custom value can be
 * returned.
 */

function lp_openssl_random_pseudo_bytes($length, &$crypto_strong) {
	return openssl_random_pseudo_bytes($length, $crypto_strong);
}


/*
 * LP_HEADER:
 *
 * A simple wrapper around header().
 *
 * This is to enable unit-tests to override 
 * this function so that a custom value can be
 * returned.
 */

function lp_header($header_str) {
	return header($header_str);
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
					$lp_config["db_dsn"],
					(isset($lp_config["db_user"])) ? $lp_config["db_user"] : "", 
					(isset($lp_config["db_pass"])) ? $lp_config["db_pass"] : ""
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


	// Set autocommit on, if so configured.
	if (
		(isset($lp_config["db_autocommit"]) === TRUE) && 
		($lp_config["db_autocommit"] === TRUE)
	) {
		$db_conn->setAttribute(PDO::ATTR_AUTOCOMMIT, TRUE);
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

function lp_http_curl_request(
	&$curl_handle,
	$uri,
	$req_header_params_arr = array(),
	$req_body_params_arr = array(),
	$req_type
) {

	/*
	 * If no content-type header set, set it now.
	 * It can be altered, if needed.
	 */

	if (isset($req_header_params_arr['content-type']) === FALSE) {
		$req_header_params_arr['content-type'] = 'application/json';
	}


	/*
	 * Call callback function for preparation
	 */
	list(
		$req_header_params_arr,
		$req_body_params_arr
	) = lp_filter_apply(
		$req_type === 'oauth2_scopes' ?
			'oauth2_scopes_call_pre' :
			'oauth2_auth_call_pre',
		array(
			$req_header_params_arr,
			$req_body_params_arr
		)
	);


	/* 
	 * Use cURL to send a POST request to the OAuth server,
	 * asking to verify the given username and password in
	 * the request given to us, using the client credentials
	 * we were configured with.
	 *
	 */

	$curl_handle = curl_init($uri);

	$curl_req_body = json_encode($req_body_params_arr);

	/* Set content-length of the body */
	$req_header_params_arr['content-length'] = (string) strlen($curl_req_body);

	curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 	"POST");
	curl_setopt($curl_handle, CURLOPT_USERAGENT, 		"login-page/" . LP_VERSION);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 	TRUE);
	curl_setopt($curl_handle, CURLOPT_HEADER, 		0);
	curl_setopt($curl_handle, CURLOPT_POST, 		TRUE);
	curl_setopt($curl_handle, CURLOPT_BINARYTRANSFER,	TRUE);

	$tmp_headers_params_arr = array();
	foreach (array_keys($req_header_params_arr) as $tmp_header_param_key) {
		$tmp_headers_params_arr[] = $tmp_header_param_key . ': ' .
			$req_header_params_arr[$tmp_header_param_key];
	}

	curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $tmp_headers_params_arr);

	curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $curl_req_body);

	$oauth_req_response_json = curl_exec($curl_handle);


	/*
	 * Apply post-filters
	 */

	list(
		$curl_handle,
		$oauth_req_response_json
	) = lp_filter_apply(
		$req_type === 'oauth2_scopes' ?
			'oauth2_scopes_call_post' :
			'oauth2_auth_call_post',
		array(
			$curl_handle,
			$oauth_req_response_json
		)
	);

	return $oauth_req_response_json;
}


/*
 * LP_CURL_GETINFO:
 *
 * A simple wrapper around curl_getinfo().
 *
 * This is to enable unit-tests to override 
 * this function so that a custom value can be
 * returned.
 */

function lp_curl_getinfo(&$curl_handle) {
	return curl_getinfo($curl_handle);
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
			array(),
			array(),
			'oauth2_scopes'
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


