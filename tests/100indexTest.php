<?php

require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/shared.php");

class IndexTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		global $lp_config;

		PHPUnit_Framework_Error_Notice::$enabled = TRUE;

		$lp_config = __lp__unittesting_lp_config_fake();

		$lp_config["lp_scope_info_get_func"] = "__lp_unittesting_html_lp_scope_info_get_success";
		$lp_config["session_start_func"] = "__lp_unittesting_session_start";
		$lp_config["header_func"] = "__lp_unittesting_header_func";

	}

	public function tearDown() {
		global $lp_config;

		__lp_unittesting_header_func(""); // Clear out saved headers

		unset($lp_config);
	}

	public function __nocaching_headers() {
		return array(
			"Cache-Control: no-cache, no-store, must-revalidate", 
			"Pragma: no-cache", 
			"Expires: 0", 
			"X-Frame-Options: DENY", 
		);
	}

	public function test_lp_login_form_ok() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"response_type"} = "token";
			$_REQUEST{"client_id"} = "testclient";

			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-api";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();


			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			ob_end_clean();	
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}
	

		$this->assertTrue(
			mb_strstr($tpl_code, '<form action="/" method="POST">') !== FALSE
		);

		$this->assertTrue(
			mb_strstr($tpl_code, '<input type="hidden" name="response_type" value="token">') !== FALSE
		);

		$this->assertTrue(
			mb_strstr($tpl_code, '<input type="hidden" name="client_id" value="testclient">') !== FALSE
		);

		$this->assertTrue(
			mb_strstr($tpl_code, '<input type="hidden" name="redirect_uri" value="http://127.0.0.4/redirect_uri">') !== FALSE
		);

		$this->assertTrue(
			mb_strstr($tpl_code, '<input type="hidden" name="scope" value="my-api">') !== FALSE
		);

		$this->assertTrue(
			mb_strstr($tpl_code, '<input type="hidden" name="state" value="' . $_REQUEST{"state"} . '">') !== FALSE
		);

		$this->assertTrue(
			mb_strstr($tpl_code, '</html>') !== FALSE
		);

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_func(FALSE),
			$this->__nocaching_headers()
		);
	}


	/*
	 * Now try to get the form, but with various missing or malformed parameters.
	 */

	public function test_lp_login_form_user_agent_mismatch() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"response_type"} = "token";
			$_REQUEST{"client_id"} = "testclient";

			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-api";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();


			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			ob_end_clean();	

			// And another request, but fake another browser
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 3.0";

			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(FALSE); // Should not happen; exception should occur
		}

		catch (Exception $e) {
			// This exception should occur, when lp_session_init() tries to destroy a cookie.
			$this->assertContains("Cannot modify header information - headers already sent by", $e->getMessage());
		}

		ob_end_clean();	
	
		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_func(FALSE),
			$this->__nocaching_headers()
		);
	}


	public function test_lp_login_form_response_type_missing() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"client_id"} = "testclient";

			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-api";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();


			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			// This exception should occur, when lp_session_init() tries to destroy a cookie.
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_func(FALSE),
			$this->__nocaching_headers()
		);
	}

	public function test_lp_login_form_response_type_invalid() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"client_id"} = "testclient";
			$_REQUEST{"response_type"} = "invalid";
			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-api";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();


			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			// This exception should occur, when lp_session_init() tries to destroy a cookie.
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_func(FALSE),
			$this->__nocaching_headers()
		);
	}


	// Note: Only client_id missing, else fine - same applies elsewhere.
	public function test_lp_login_form_client_id_missing() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"response_type"} = "token";
			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-api";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();


			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			// This exception should occur, when lp_session_init() tries to destroy a cookie.
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_func(FALSE),
			$this->__nocaching_headers()
		);
	}

	public function test_lp_login_form_redirect_uri_missing() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"client_id"} = "testclient";
			$_REQUEST{"response_type"} = "token";
			$_REQUEST{"scope"} = "my-api";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();


			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			// This exception should occur, when lp_session_init() tries to destroy a cookie.
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_func(FALSE),
			$this->__nocaching_headers()
		);
	}


	public function test_lp_login_form_scope_missing() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"client_id"} = "testclient";
			$_REQUEST{"response_type"} = "token";
			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();


			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			// This exception should occur, when lp_session_init() tries to destroy a cookie.
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_func(FALSE),
			$this->__nocaching_headers()
		);
	}


	public function test_lp_login_form_scope_mismatch() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"client_id"} = "testclient";
			$_REQUEST{"response_type"} = "token";
			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-apii";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();


			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			// This exception should occur, when lp_session_init() tries to destroy a cookie.
			$this->assertEquals($e->getMessage(), "Could not get information about requested scope");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_func(FALSE),
			$this->__nocaching_headers()
		);
	}


	public function test_lp_login_form_state_missing() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"client_id"} = "testclient";
			$_REQUEST{"response_type"} = "token";
			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-api";


			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			// This exception should occur, when lp_session_init() tries to destroy a cookie.
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_func(FALSE),
			$this->__nocaching_headers()
		);
	}

	
	/*
	 * Here deal with when username and passwords are not sent - everything else is fine.
	 */

	public function test_lp_login_try_username_missing() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"client_id"} = "testclient";
			$_REQUEST{"response_type"} = "token";
			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-api";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();

			$_REQUEST{"password"} = "somePass";

			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(
				mb_strstr($tpl_code, '<input type="hidden" name="response_type" value="token">') !== FALSE
			);

			$this->assertTrue(
				mb_strstr($tpl_code, '<input type="hidden" name="redirect_uri" value="http://127.0.0.4/redirect_uri">') !== FALSE
			);
		}

		catch (Exception $e) {
			// This exception should occur, when lp_session_init() tries to destroy a cookie.
			$this->assertEquals($e->getMessage(), "");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_func(FALSE),
			$this->__nocaching_headers()
		);
	}


	public function test_lp_login_try_password_missing() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"client_id"} = "testclient";
			$_REQUEST{"response_type"} = "token";
			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-api";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();

			$_REQUEST{"username"} = "someUSER";

			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(
				mb_strstr($tpl_code, '<input type="hidden" name="response_type" value="token">') !== FALSE
			);

			$this->assertTrue(
				mb_strstr($tpl_code, '<input type="hidden" name="redirect_uri" value="http://127.0.0.4/redirect_uri">') !== FALSE
			);
		}

		catch (Exception $e) {
			// This exception should occur, when lp_session_init() tries to destroy a cookie.
			$this->assertEquals($e->getMessage(), "");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_func(FALSE),
			$this->__nocaching_headers()
		);
	}


	/*
	 * After this point, deal with actual login-attempts
	 * - i.e. usernames and passwords are sent - but 
	 * with various other parameters missing 
	 * or else everything fine.
	 */

	public function test_lp_login_try_ok() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"client_id"} = "testclient";
			$_REQUEST{"response_type"} = "token";
			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-api";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();

			$_POST{"username"} = "user50";
			$_POST{"password"} = "reallypredictable";

			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			/*
		 	 * Now harvest the nonce string from the result
			 */

			$this->assertTrue(
				mb_strstr($tpl_code, '<input type="hidden" name="nonce" value="') !== FALSE
			);

			$tpl_code_nonce_index = mb_strstr($tpl_code, '<input type="hidden" name="nonce" value="');
			$tpl_code_nonce_ends_index = mb_strpos($tpl_code_nonce_index, '">');

			// Harvested!
			$req_token = mb_substr(
				$tpl_code_nonce_index, 
				mb_strlen('<input type="hidden" name="nonce" value="'), 
				$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
			);

			ob_end_clean();

			// Test if only no-caching headers are in place
			$this->assertEquals(
				__lp_unittesting_header_func(FALSE),
				$this->__nocaching_headers()
			);

			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */


			$_POST{"nonce"} = $req_token;

			// Fake curl call that will always return access token
			$lp_config["lp_http_curl_request_func"] = 
				"__lp_unittesting_lp_http_curl_request_fake_successful_oauth_login";

			__lp_unittesting_lp_http_curl_getinfo(FALSE, array(
				"url" => "http://127.0.0.3/oauth-login-page-grant",
				"content_type" => "application/json",
				"http_code" => 200,
				"header_size" => 322,
    				"request_size" => 354,
				"filetime" => -11,
				"ssl_verify_result" => 0,
				"redirect_count" => 0,
				"total_time" => 0.300998,
				"namelookup_time" => 0.000119,
				"connect_time" => 0.00039,
				"pretransfer_time" => 0.000529,
				"size_upload" => 199,
				"size_download" => 80,
				"speed_download" => 26,
				"speed_upload" => 661,
				"download_content_length" => 80,
				"upload_content_length" => 199,
				"starttransfer_time" => 0.300776,
				"redirect_time" => 0,
				"certinfo" => Array(        ),
				"primary_ip" => "127.0.0.3",
				"primary_port" => 80,
				"local_ip" => "127.0.0.3",
				"local_port" => 38857,
				"redirect_url" => ""
			));

			$lp_config["lp_http_curl_getinfo_func"] = 
				"__lp_unittesting_lp_http_curl_getinfo";

			__lp_unittesting_header_func(""); // Clean out headers

			ob_start();

			include(__DIR__ . "/../index.php");
			
			$tpl_code_2 = ob_get_contents();

			$headers_sent = __lp_unittesting_header_func(FALSE);

			$this->assertTrue(
				in_array("Location: http://127.0.0.4/redirect_uri#access_token=KSiuuuuuuuuuuuuuuuuuuuuuuuu99999999999&token_type=Bearer&expires_in=3600", $headers_sent)
			);

			$this->assertTrue(
				in_array("Cache-Control: no-cache, no-store, must-revalidate", $headers_sent)
			);

			$this->assertTrue(
				in_array("Pragma: no-cache", $headers_sent)
			);
			$this->assertTrue(
				in_array("Expires: 0", $headers_sent)
			);

			$this->assertTrue(
				in_array("X-Frame-Options: DENY", $headers_sent)
			);

			$this->assertTrue(
				in_array("HTTP/1.1 302 Found", $headers_sent)
			);

			$this->assertTrue(
				$tpl_code_2 == ""
			);

		}

		catch (Exception $e) {
			// This exception should occur, when lp_session_init() tries to destroy a cookie.
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		ob_end_clean();	
	}

	// FIXME:  Test with missing client_id, etc.
	// FIXME: Also mismatching user-agent
	// FIXME: Invalid nonce token
	// FIXME: Expired nonce token
	public function test_lp_login_try_() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
#			$_REQUEST{"client_id"} = "testclient";
			$_REQUEST{"response_type"} = "token";
			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-api";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();


			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			// This exception should occur, when lp_session_init() tries to destroy a cookie.
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		ob_end_clean();	
	}


}

