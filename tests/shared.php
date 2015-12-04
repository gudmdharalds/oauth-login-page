<?php

global $lp_config;

function __lp_unittesting_misc_lp_fatal_error($error_msg) {
        global $lp_unittesting_fatals;

        $lp_unittesting_fatals = TRUE;

        throw new Exception($error_msg);
}


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

function __lp_unittesting_html_time_func() {
        return 1449146192;
}

function __lp_unittesting_html_openssl_random_pseudo_bytes_func($length, &$crypto_strong) {
	$crypto_strong = TRUE;

        return "openssl_randomstring_butnotreally";
}

function __lp_unittesting_session_start() {
        global $_SESSION;

        return TRUE;
}                       

function __lp__unittesting_lp_config_real() {
	$lp_config = lp_config_original();	// Call the original function, to get real, user-defined settings.
	$lp_config["lp_scope_info_get_func"] = "lp_scope_info_get_original";

	// FIXME: fix this
	#$lp_config["db_name"] = $lp_config["db_name"] . "_test";		// Use test DB

	return $lp_config;
}

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
	$lp_config["session_hashing_function"]		= "sha256";
	$lp_config["session_entropy_length"]		= "768";
	$lp_config["session_secret_function"]		= "sha256";
	$lp_config["db_dsn"]				= "sqlite:/tmp/memory.sqlite";

	$lp_config["openssl_random_pseudo_bytes_func"]	= "openssl_random_pseudo_bytes";
	$lp_config["time_func"]                         = "time";

	$lp_config["lp_scope_info_get_func"]		= "lp_scope_info_get_original";

	return $lp_config;
}

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
			print_r($lp_config);
			die("Could not recreate database table: " . $e->getMessage());
		}
	}
}

// Fake lp_fatal_error() to be another function (above)
runkit_function_redefine("lp_fatal_error", '$error_msg', 'return __lp_unittesting_misc_lp_fatal_error($error_msg);');

// Fake lp_scope_info_get() to be whatever you want (configurable, see above)
$lp_config["lp_scope_info_get_func"] = "lp_scope_info_get_original";
runkit_function_rename("lp_scope_info_get", "lp_scope_info_get_original");
runkit_function_add("lp_scope_info_get", '', 'return __lp_unittesting_html_lp_scope_info_get();');

// Provide lp_config() - but with a different name to empasize that this is (mostly) the real thing.
runkit_function_rename("lp_config", "lp_config_original");
runkit_function_add("lp_config_real", '', 'return __lp__unittesting_lp_config_real();');
runkit_function_add("lp_config", '', 'return __lp__unittesting_lp_config_fake();'); // FIXME: Not elegant



