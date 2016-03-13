<?php

require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../includes.php");
require_once(__DIR__ . "/shared.php");


class MiscTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		global $lp_config;

		PHPUnit_Framework_Error_Notice::$enabled = TRUE;

		$lp_config = __lp__unittesting_lp_config_fake();

		// Save snapshot
		__lp__unittesting_superglobals_snapshot(TRUE);
	}

	public function tearDown() {
		global $lp_config;

		unset($lp_config);

		// Put snapshot in place
		__lp__unittesting_superglobals_snapshot(FALSE);
	}


	public function test_lp_init_check_ok() {
		global $lp_config;

		try {
			lp_init_check();
		}

		catch (Exception $e) {
			$this->assertEquals("", $e->getMessage());
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
			$this->assertEquals(
				'Incorrectly configured. Missing setting: "session_hashing_function"', 
				$e->getMessage()
			);
		}

		// Try with garbled
		$lp_config["session_hashing_function"] = "notexisting";

		try {
			lp_init_check();

			$this->assertFalse(TRUE); 
		}

		catch (Exception $e) {
			$this->assertEquals(
				"This setup is not capable of generating random tokens (problematic function: session_hashing_function).",
				$e->getMessage()
			);
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
			$this->assertEquals(
				'Incorrectly configured. Missing setting: "session_secret_function"',
				$e->getMessage()
			);
		}
	
		// Try with garbled
		$lp_config["session_secret_function"] = "notexisting";

		try {
			lp_init_check();

			$this->assertFalse(TRUE); 
		}

		catch (Exception $e) {
			$this->assertEquals(
				"This setup is not capable of generating random tokens (problematic function: session_secret_function).",
				$e->getMessage()
			);
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
			$this->assertEquals(
				'Incorrectly configured. Missing setting: "nonce_hashing_function"',
				$e->getMessage()
			);
		}

		// Try with garbled
		$lp_config["nonce_hashing_function"] = "notexisting";

		try {
			lp_init_check();

			$this->assertFalse(TRUE); 
		}

		catch (Exception $e) {
			$this->assertEquals(
				"This setup is not capable of generating random tokens (problematic function: nonce_hashing_function).",
				$e->getMessage()
			);
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
			"db_dsn",
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
				$this->assertEquals(
					"Incorrectly configured. Missing setting: \"" . $check_config_key . "\"",
					$e->getMessage()
				);
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
			$this->assertEquals(
				"Unable to set PHP configuration option \"someveryrandomKEY\"",
				$e->getMessage()
			);
		}
	}


	public function test_lp_time() {
		global $lp_config;

		$lp_config["time_func"] = "time";


		try {
			$time_test = lp_time();
			$time_real = time();

			$this->assertTrue(
				($time_test == $time_real - 1) ||
				($time_test == $time_real + 0) ||
				($time_test == $time_real + 1)
			);
		}

		catch (Exception $e) {
			$this->assertEquals("", $e->getMessage());
		}
	}


	public function test_lp_openssl_random_pseudo_bytes() {
		global $lp_config;

		$lp_config["openssl_random_pseudo_bytes_func"] = "openssl_random_pseudo_bytes";

		$crypto_strong = FALSE;

		try {
			$random_bytes = lp_openssl_random_pseudo_bytes(100, $crypto_strong);

			$this->assertTrue(
				(strlen($random_bytes) > 0)
			);

			$this->assertTrue(
				$crypto_strong
			);
		}

		catch (Exception $e) {
			$this->assertEquals("", $e->getMessage());
		}
	}


	public function test_lp_db_pdo_init() {
		global $lp_config;

		$lp_config["db_dsn"] = "somedriver";

		try {
			lp_db_pdo_init();

			$this->assertFalse(TRUE); 
		}

		catch (Exception $e) {
			$this->assertEquals("Could not connect to database: invalid data source name", $e->getMessage());
		}
	}


	/*
	 * No test for lp_http_curl_request() - 
	 * no private fixed endpoint that is universal
	 * on all systems.
	 */

	public function test_lp_scope_info_get() {
		global $lp_config;


		$old_lp_config = $lp_config;

		/*
		 * First, invoke the actual configuration file,
		 * so that we get some actual, correct values.
		 */

		$lp_config = lp_config_real(); 

		try {
			lp_init_check();

			$lp_config["lp_scope_info_get_func"] = "lp_scope_info_get_original";

			$lp_config["lp_http_curl_request_func"] = "lp_http_curl_request_original";

			$scopes_info = lp_scope_info_get();
		}

		catch (Exception $e) {
			$this->assertEquals("", $e->getMessage());
		}

		$lp_config = $old_lp_config;

		$this->assertTrue(
			empty($scopes_info) !== TRUE
		);	
	}
}


