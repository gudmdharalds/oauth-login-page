<?php

global $lp_config;

/*
 * Replacement for lp_fatal_error()
 */

function __lp_unittesting_misc_lp_fatal_error($error_msg) {
        global $lp_unittesting_fatals;

        $lp_unittesting_fatals = TRUE;

        throw new Exception($error_msg);
}


/*
 * Scope Information functions 
 */

function __lp_unittesting_html_lp_scope_info_get() {
	global $lp_config;


	if (isset($lp_config["lp_scope_info_get_func"]) === TRUE) {
		return call_user_func($lp_config["lp_scope_info_get_func"]);
	}

	else {
		lp_fatal_error("lp_scope_info_get_func not defined in \$lp_config");
	}
}

function __lp_unittesting_html_lp_scope_info_get_success() {
        return (array) json_decode('{ "my-api": "My API access", "profile": "Access to profile" }');
}

function __lp_unittesting_html_lp_scope_info_get_error() {
	global $lp_config;

        return FALSE;
}


/*
 * Replacements for time()
 */

function __lp_unittesting_html_time_func() {
	global $lp_config;

	if (($ret = (call_user_func($lp_config["time_func"]))) === FALSE) {
		die("Could not call configured time function!");
	}

	return $ret;
}

function __lp_unittesting_html_time_static_func() {
        return 1449146192;
}


/*
 * Replacement for openssl_random_pseudo_bytes()
 */

function __lp_unittesting_html_openssl_random_pseudo_bytes_func($length, &$crypto_strong) {
	global $lp_config;

	$crypto_strong = FALSE;

	if (($ret = (call_user_func_array($lp_config["openssl_random_pseudo_bytes_func"], array($length, &$crypto_strong)))) === FALSE) {
		lp_fatal_error("Could not get random bytes!");
	}

	return $ret;
}

function __lp_unittesting_html_openssl_random_pseudo_bytes_static_func($length, &$crypto_strong) {
	$crypto_strong = TRUE;

        return "openssl_randomstring_butnotreally";
}


/*
 * Replacement for header()
 */

function __lp_unittesting_header_func($header_str) {
	global $lp_config;

	if (($ret = (call_user_func_array($lp_config["header_func"], array($header_str)))) === FALSE) {
		lp_fatal_error("Could not send header!");
	}

	return $ret;
}

function __lp_unittesting_header_aggregating_func($header_str) {
	static $headers_all = array();

	if ($header_str === "") {
		$headers_all = Array();
		return $headers_all; 
	}

	if ($header_str === FALSE) {
		return $headers_all;
	}

	array_push($headers_all, $header_str);
	
	return (string) $header_str;
}


/*
 * Replacement for session_start()
 */
 
function __lp_unittesting_session_start() {
	global $lp_config;

	if (($ret = (call_user_func_array($lp_config["session_start_func"], array()))) === FALSE) {
		die("Could not call set session_start_func");
	}

	return $ret;
}                      

function __lp_unittesting_session_static_start() {
        global $_SESSION;

        return TRUE;
}


/* 
 * Replacements for lp_http_curl_request
 */

function __lp_unittesting_lp_http_curl_request(&$curl_handle, $uri, $req_header_params_arr, $req_body_params_arr, $req_type) {
	global $lp_config;

	if (($ret = (call_user_func_array($lp_config["lp_http_curl_request_func"], array(&$curl_handle, $uri, $req_header_params_arr, $req_body_params_arr, $req_type)))) === FALSE) {
	}

	return $ret;
}

function __lp_unittesting_lp_http_curl_request_fake_successful_oauth_login(&$curl_handle, $uri, $req_body_params_arr) {
	return '{"access_token":"KSiuuuuuuuuuuuuuuuuuuuuuuuu99999999999","token_type":"Bearer","expires_in":3600}';
}

function __lp_unittesting_lp_http_curl_request_fake_failed_oauth_login(&$curl_handle, $uri, $req_body_params_arr) {
	return '{"error":"invalid_credentials","message":"The user credentials were incorrect."}';
}

function __lp_unittesting_lp_http_curl_request_fake_failed_oauth_login_json_corruption(&$curl_handle, $uri, $req_body_params_arr) {
	return '{"erroralid_credentials","message":"The user credentials were incorrect."}';
}


/*
 * Replacement for lp_http_curl_getinfo()
 */

function __lp_unittesting_lp_http_curl_getinfo($curl_handle, $to_store = FALSE) {
	static $to_return;

	if ($to_store === FALSE) {
		return $to_return;
	}

	if ($curl_handle === FALSE) {
		$to_return = $to_store;
	}

	return $to_return;
}


/*
 * lp_config() replacement - using the real lp_config(),
 * except for the database DSN.
 */

function __lp__unittesting_lp_config_real() {
	$lp_config = lp_config_original();	// Call the original function, to get real, user-defined settings.

	// Replace db_dsn with test.
	$lp_config["db_dsn"] = $lp_config["db_dsn_test"];

	return $lp_config;
}


/*
 * lp_config() replacement - using totally fake configuration.
 */

function __lp__unittesting_lp_config_fake() {
	$lp_config = array();

	$lp_config["image_page"] 			= "/static/image_page.png";
	$lp_config["image_icon"] 			= "/static/image_icon.png";
	$lp_config["page_title_prefix"] 		= "Page Title Prefix";
	$lp_config["css_file"]				= "/static/css_file.css";
	$lp_config["login_form_heading"]		= "Login Form Heading";
	$lp_config["login_form_error_prefix"]		= "Login Form Error Prefix";
	$lp_config["login_form_error_suffix"]		= "Login Form Error Suffix";
	$lp_config["nonce_static_secret_key"]		= "Nonce_static_secRET_KEy";
	$lp_config["nonce_hashing_function"]		= "sha256";
	$lp_config["oauth2_server_access_token_uri"]	= "http://127.0.0.3/access_token";
	$lp_config["oauth2_server_scopes_info_uri"]	= "http://127.0.0.4/scopes_info";
	$lp_config["oauth2_grant_type"]			= "oauthloginpage";
	$lp_config["session_hashing_function"]		= "sha256";
	$lp_config["session_entropy_length"]		= "768";
	$lp_config["session_secret_function"]		= "sha256";

	// Save DB with a randomized filename
	$lp_config["db_dsn_file_name"]			= tempnam("/tmp", "lp_sqlite.db." . time());
	$lp_config["db_dsn"]				= "sqlite:" . $lp_config["db_dsn_file_name"];
	$lp_config["db_autocommit"]			= FALSE;

	$lp_config["openssl_random_pseudo_bytes_func"]	= "openssl_random_pseudo_bytes";
	$lp_config["time_func"]                         = "time";
	$lp_config["lp_http_curl_request_func"]		= "lp_http_curl_request_original";

	$lp_config["lp_scope_info_get_func"]		= "__lp_unittesting_html_lp_scope_info_get_success";

	return $lp_config;
}

function __lp_unittesting_lp_config_cleanups() {
	global $lp_config;

	if (isset($lp_config["db_dsn_file_name"])) {
		unlink($lp_config["db_dsn_file_name"]);
	}
}

/*
 * Prepare DB for unit-testing.
 */

function __lp__unittesting_lp_db_test_prepare() {
	global $lp_config;

	try {
		$db_conn = lp_db_pdo_init();

		$db_stmt = $db_conn->prepare("DROP TABLE lp_sessions");
		$db_stmt->execute();


		$db_res = $db_conn->query("CREATE TABLE `lp_sessions` (
			  `session_id` varchar(255) NOT NULL DEFAULT '',
			  `session_expires` int(10) NOT NULL DEFAULT '0',
			  `session_data` text,
			  PRIMARY KEY (`session_id`)
			)");


		if ($db_res === FALSE) {
			die("Could not recreate database table");
		}
	}
        
	catch (Exception $e) {
		if ($e->getMessage() != "") {
			die("Could not recreate database table: " . $e->getMessage());
		}
	}
}


/*
 * 
 * When called with $snapshot_create is TRUE:
 *
 * Save snapshot of superglobals for use when
 * SetUp() of test-classes is called.
 *
 * Else, put saved snapshot in place. 
 */

function __lp__unittesting_superglobals_snapshot($snapshot_create = FALSE) {
	static $superglobals_snapshot = array();
	global $_SERVER;
	global $_GET;
	global $_POST;
	global $_FILES;
	global $_COOKIE;
	global $_SESSION;
	global $_REQUEST;
	global $_ENV;


	if ($snapshot_create === TRUE) {
		$superglobals_snapshot = array(
			"_SERVER"	=> $_SERVER,
			"_GET"		=> $_GET,
			"_POST"		=> $_POST,
			"_FILES"	=> $_FILES,
			"_COOKIE" 	=> $_COOKIE,
			"_SESSION"	=> $_SESSION,
			"_REQUEST"	=> $_REQUEST,
			"_ENV"		=> $_ENV
		);
	}

	else {
		$_SERVER = $superglobals_snapshot["_SERVER"];
		$_GET = $superglobals_snapshot["_GET"];
		$_POST = $superglobals_snapshot["_POST"];
		$_FILES = $superglobals_snapshot["_FILES"];
		$_COOKIE = $superglobals_snapshot["_COOKIE"];
		$_SESSION = $superglobals_snapshot["_SESSION"];
		$_REQUEST = $superglobals_snapshot["_REQUEST"];
		$_ENV = $superglobals_snapshot["_ENV"];
	}
}


/*
 * Here below various replacement functions are 
 * put in place.
 */

// Fake lp_fatal_error() to be another function (above)
runkit_function_redefine("lp_fatal_error", '$error_msg', 'return __lp_unittesting_misc_lp_fatal_error($error_msg);');

// Fake lp_scope_info_get() to be whatever you want (configurable, see above)
runkit_function_rename("lp_scope_info_get", "lp_scope_info_get_original");
runkit_function_add("lp_scope_info_get", '', 'return __lp_unittesting_html_lp_scope_info_get();');

// Provide lp_config() - but with a different name to empasize that this is (mostly) the real thing.
runkit_function_rename("lp_config", "lp_config_original");
runkit_function_add("lp_config_real", '', 'return __lp__unittesting_lp_config_real();');

// Not elegant, but does the job. Needed when testing index.php
runkit_function_add("lp_config", '', 'global $lp_config; return $lp_config;'); 

// So we can test index.php 
runkit_function_rename('lp_http_curl_request', 'lp_http_curl_request_original');
runkit_function_add(
		'lp_http_curl_request', 
		' &$curl_handle, $uri, $req_header_params_arr, $req_body_params_arr, $req_type', 
		'return __lp_unittesting_lp_http_curl_request($curl_handle, $uri, $req_header_params_arr, $req_body_params_arr, $req_type);'
);

// Remove simple wrapper lp_curl_getinfo(), put our own in place.
runkit_function_remove('lp_curl_getinfo');
runkit_function_add('lp_curl_getinfo', '&$curl_handle', 'return __lp_unittesting_lp_http_curl_getinfo($curl_handle);');

// Remove simple wrapper lp_curl_getinfo(), put our own in place.
runkit_function_remove('lp_time');
runkit_function_add('lp_time', '', 'return __lp_unittesting_html_time_func();');

// Remove simple wrapper lp_openssl_random_pseudo_bytes(), put our own in place.
runkit_function_remove('lp_openssl_random_pseudo_bytes');
runkit_function_add('lp_openssl_random_pseudo_bytes', '$length, &$crypto_strong', 'return __lp_unittesting_html_openssl_random_pseudo_bytes_func($length, $crypto_strong);');

// Remove simple wrapper lp_header(), put our own in place.
runkit_function_remove('lp_header');
runkit_function_add('lp_header', '$header_str', 'return __lp_unittesting_header_func($header_str);');

// Remove simple wrapper lp_session_start(), put our own in place.
runkit_function_remove('lp_session_start');
runkit_function_add('lp_session_start', '', 'return __lp_unittesting_session_start();');




