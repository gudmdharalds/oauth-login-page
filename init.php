<?php

// FIXME: Provide unit-tests

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
			"session_entropy_length",
			"session_secret_function",
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


