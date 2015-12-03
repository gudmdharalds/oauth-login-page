<?php

require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/tests_shared.php");


class MiscTest extends PHPUnit_Framework_TestCase {
	public function __construct() {
		global $lp_config;

		PHPUnit_Framework_Error_Notice::$enabled = TRUE;

		// FIXME: Move into function?
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
		$lp_config["db_driver"]				= "sqlite3";
		$lp_config["db_name"]				= "/tmp/sqlite3_" . time() . ".db";
		$lp_config["db_host"]				= "-";
		$lp_config["db_user"]				= "-";
		$lp_config["db_pass"]				= "-";

		$lp_config["time_func"]                         = "time";

		$lp_config["lp_scope_info_get_func"]		= "lp_scope_info_get_original";
	}

	public function __destruct() {
		global $lp_config;

	}

	public function test_lp_init_check_ok() {
		global $lp_config;

		try {
			lp_init_check();
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}
	}


	/**
	 * @depends test_lp_init_check_ok
	 */

	public function test_lp_init_check_fail_session_hashing_function() {
		global $lp_config;

		unset($lp_config["session_hashing_function"]);

		try {
			lp_init_check();

			$this->assertFalse(TRUE); // This should never be run; exception should occur.
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), 'Incorrectly configured. Missing setting: "session_hashing_function"');
		}

		// Try with garbled
		$lp_config["session_hashing_function"] = "notexisting";

		try {
			lp_init_check();

			$this->assertFalse(TRUE); 
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "This setup is not capable of generating random tokens (problematic function: session_hashing_function).");
		}
	}


	/**
	 * @depends test_lp_init_check_ok
	 */

	public function test_lp_init_check_fail_session_secret_function() {
		global $lp_config;

		// Try with none
		unset($lp_config["session_secret_function"]);

		try {
			lp_init_check();

			$this->assertFalse(TRUE);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), 'Incorrectly configured. Missing setting: "session_secret_function"');
		}
	
		// Try with garbled
		$lp_config["session_secret_function"] = "notexisting";

		try {
			lp_init_check();

			$this->assertFalse(TRUE); 
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "This setup is not capable of generating random tokens (problematic function: session_secret_function).");
		}
	}

	
	/**
	 * @depends test_lp_init_check_ok
	 */

	public function test_lp_init_check_fail_nonce_hashing_function() {
		global $lp_config;

		// Try with none
		unset($lp_config["nonce_hashing_function"]);

		try {
			lp_init_check();

			$this->assertFalse(TRUE); 
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), 'Incorrectly configured. Missing setting: "nonce_hashing_function"');
		}

		// Try with garbled
		$lp_config["nonce_hashing_function"] = "notexisting";

		try {
			lp_init_check();

			$this->assertFalse(TRUE); 
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "This setup is not capable of generating random tokens (problematic function: nonce_hashing_function).");
		}
	}

	/**
	 * @depends test_lp_init_check_ok
	 */

	public function test_lp_init_check_fail_missing_value() {
		global $lp_config;

		foreach (array(
			"image_page",
			"image_icon",
			"page_title_prefix",
			"css_file",
			"login_form_heading",
			"login_form_error_prefix",
			"login_form_error_suffix",
			"oauth2_server_access_token_uri",
			"session_hashing_function",
			"session_secret_function",
			"session_entropy_length",
			"nonce_hashing_function",
			"nonce_static_secret_key",
			"db_driver",
			"db_name",
			"db_host",
			"db_user",
			"db_pass",
                ) as $check_config_key) {
			$this->assertTrue(isset($lp_config[$check_config_key]));
		
			$backup_config_key_value = $lp_config[$check_config_key];

			// Try with missing
			unset($lp_config[$check_config_key]);

			try {
				lp_init_check();

				$this->assertFalse(TRUE); 
			}

			catch (Exception $e) {
				$this->assertEquals($e->getMessage(), "Incorrectly configured. Missing setting: \"" . $check_config_key . "\"");
			}

			$lp_config[$check_config_key] = $backup_config_key_value;
		}
	}

	public function test_lp_ini_set() {
		try {
			lp_ini_set("someveryrandomKEY", "somevalue");

			$this->assertFalse(TRUE); 
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "Unable to set PHP configuration option \"someveryrandomKEY\"");
		}
	}

	public function test_lp_db_pdo_init() {
		global $lp_config;

		$lp_config["db_driver"] = "somedriver";

		try {
			lp_db_pdo_init();

			$this->assertFalse(TRUE); 
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "Could not connect to database: could not find driver");
		}
	}


	/*
	 * No test for lp_http_curl_request() - 
	 * no private fixed endpoint that is universal
	 * on all systems.
	 */

	public function test_lp_scope_info_get() {
		global $lp_config;


		/*
		 * First, invoke the actual configuration file,
		 * so that we get some actual, correct values.
		 */

		// FIXME: Should we really be doing this?
		$lp_config = lp_config(); 

		try {
			lp_init_check();

			$lp_config["lp_scope_info_get_func"] = "lp_scope_info_get_original";

			lp_scope_info_get();
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}
	}


	// FIXME: Tests for lp_time() and lp_openssl_random_pseudo_bytes()
}




