<?php

require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../includes.php");
require_once(__DIR__ . "/shared.php");

class IndexTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		global $lp_config;

		PHPUnit_Framework_Error_Notice::$enabled = TRUE;

		$lp_config = __lp__unittesting_lp_config_fake();

		$lp_config["lp_scope_info_get_func"] = "__lp_unittesting_html_lp_scope_info_get_success";
		$lp_config["session_start_func"] = "__lp_unittesting_session_static_start";
		$lp_config["header_func"] = "__lp_unittesting_header_aggregating_func";

		// Save snapshot
		__lp__unittesting_superglobals_snapshot(TRUE);
	}


	public function tearDown() {
		global $lp_config;

		__lp_unittesting_header_aggregating_func(""); // Clear out saved headers

		unset($lp_config);

		// Put snapshot in place
		__lp__unittesting_superglobals_snapshot(FALSE);
	}


	public function __nocaching_headers_prototype() {
		// Normal headers sent when only form is displayed
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
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
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
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);

		$this->assertContains('<input type="hidden" name="response_type" value="token">', $tpl_code);
		$this->assertContains('<input type="hidden" name="client_id" value="testclient">', $tpl_code);
		$this->assertContains('<input type="hidden" name="redirect_uri" value="http://127.0.0.4/redirect_uri">', $tpl_code);
		$this->assertContains('<input type="submit" value="Log in">', $tpl_code);
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
			// missing response_type

			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			// This exception should occur
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);
	}


	public function test_lp_login_form_response_type_invalid() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"client_id"} = "testclient";
			$_REQUEST{"response_type"} = "invalid";	 // invalid response type
			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-api";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();


			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code = ob_get_contents();

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			// This exception should occur.
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);
	}


	public function test_lp_login_form_client_id_missing() {
		global $lp_config;
		global $_SERVER;

		try {
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
			$_REQUEST{"response_type"} = "token";
			$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
			$_REQUEST{"scope"} = "my-api";
			$_REQUEST{"state"} = "state-" . time() . "-" . rand();
			// client_id missing


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
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
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
			// redirect_uri missing

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
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
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
			// scope missing

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
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
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
			$_REQUEST{"scope"} = "my-apii"; // mismaching scope; not defined
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
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
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
			// state missing 


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
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
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
			$this->assertEquals($e->getMessage(), "");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
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
			$this->assertEquals($e->getMessage(), "");
		}

		ob_end_clean();	

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
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

		$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 2.0";
		$_REQUEST{"client_id"} = "testclient";
		$_REQUEST{"response_type"} = "token";
		$_REQUEST{"redirect_uri"} = "http://127.0.0.4/redirect_uri";
		$_REQUEST{"scope"} = "my-api";
		$_REQUEST{"state"} = "state-" . time() . "-" . rand();

		$_POST{"username"} = "user50";
		$_POST{"password"} = "reallypredictable";


		/*
		 * Begin by making a 'request' -- but only
		 * for the purposes of extracting nonce string.
		 */

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);


		// Now, try another request
		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */


			$_POST{"nonce"} = $req_nonce_string; // Nonce in place.

			// Fake curl call that will always return access token
			$lp_config["lp_http_curl_request_func"] = 
				"__lp_unittesting_lp_http_curl_request_fake_successful_oauth_login";

			// Fake some curl information 
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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			ob_start();

			include(__DIR__ . "/../index.php");
			
			$tpl_code_2 = ob_get_contents();

			$this->assertEquals(
				__lp_unittesting_header_aggregating_func(FALSE),

				array_merge(
					$this->__nocaching_headers_prototype(), // Standard headers
					array(					// And then redirection stuff
						"HTTP/1.1 302 Found", 
						"Location: http://127.0.0.4/redirect_uri#access_token=KSiuuuuuuuuuuuuuuuuuuuuuuuu99999999999&token_type=Bearer&expires_in=3600"
					)
				)
			);


			$this->assertTrue(
				$tpl_code_2 == ""
			);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}

		ob_end_clean();	
	}


	public function test_lp_login_try_oauth_server_json_error() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);


		// Try another request
		try {

			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */


			$_POST{"nonce"} = $req_nonce_string;

			// Fake curl call that will always return corrupted JSON 
			$lp_config["lp_http_curl_request_func"] = 
				"__lp_unittesting_lp_http_curl_request_fake_failed_oauth_login_json_corruption";

			// Fake some curl information 
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

			// FIXME: Do test for no-caching headers!
			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			ob_start();

			include(__DIR__ . "/../index.php");
			

			$this->assertTrue(FALSE); // Should never be run; exception should take place
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "Could not decode response from OAuth server");
		}

		$tpl_code_2 = ob_get_contents();

		$this->assertEquals($tpl_code_2, "");

		ob_end_clean();	
	}


	public function test_lp_login_try_oauth_server_login_failure() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();
	
		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);


		// Try another request
		try {
			$_POST{"nonce"} = $req_nonce_string;

			// Fake curl call that will not return an access token, but failure with error
			$lp_config["lp_http_curl_request_func"] = 
				"__lp_unittesting_lp_http_curl_request_fake_failed_oauth_login"; 

			// Fake some curl information 
			__lp_unittesting_lp_http_curl_getinfo(FALSE, array(
				"url" => "http://127.0.0.3/oauth-login-page-grant",
				"content_type" => "application/json",
				"http_code" => 401,
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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			ob_start();

			include(__DIR__ . "/../index.php");
	
			$tpl_code_2 = ob_get_contents();

			$this->assertContains("Login Form Error Prefix The user credentials were incorrect. Login Form Error Suffix", $tpl_code_2);
	
			// Test if only no-caching headers are in place
			$this->assertEquals(
				__lp_unittesting_header_aggregating_func(FALSE),
				$this->__nocaching_headers_prototype()
			);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}

		ob_end_clean();	
	}


	public function test_lp_login_try_client_id_missing() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);


		// Try another request
		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */


			$_POST{"nonce"} = $req_nonce_string;

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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			unset($_REQUEST{"client_id"}); // Remove client_id 

			ob_start();

			include(__DIR__ . "/../index.php");

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		$tpl_code_2 = ob_get_contents();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);	

		$this->assertTrue(
			$tpl_code_2 == ""
		);

		ob_end_clean();	
	}


	public function test_lp_login_try_response_type_missing() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);


		// Try another request
		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */


			$_POST{"nonce"} = $req_nonce_string;

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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			unset($_REQUEST{"response_type"}); // Remove response_type

			ob_start();

			include(__DIR__ . "/../index.php");

			$this->assertTrue(FALSE);

		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		$tpl_code_2 = ob_get_contents();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);	

		$this->assertTrue(
			$tpl_code_2 == ""
		);

		ob_end_clean();	
	}

	public function test_lp_login_try_response_type_invalid() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);


		// Try another request
		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */


			$_POST{"nonce"} = $req_nonce_string;

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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			$_REQUEST{"response_type"} = "Invalid"; // Invalid response_type

			ob_start();

			include(__DIR__ . "/../index.php");

			$this->assertTrue(FALSE);

		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		$tpl_code_2 = ob_get_contents();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);	

		$this->assertTrue(
			$tpl_code_2 == ""
		);

		ob_end_clean();	
	}


	public function test_lp_login_try_redirect_uri_missing() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);


		// Try another request
		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */


			$_POST{"nonce"} = $req_nonce_string;

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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			unset($_REQUEST{"redirect_uri"}); // Remove redirect_uri

			ob_start();

			include(__DIR__ . "/../index.php");

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		$tpl_code_2 = ob_get_contents();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);	

		$this->assertTrue(
			$tpl_code_2 == ""
		);

		ob_end_clean();	
	}


	public function test_lp_login_try_scope_missing() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);


		// Try another request
		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */


			$_POST{"nonce"} = $req_nonce_string;

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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			unset($_REQUEST{"scope"}); // Remove scope 

			ob_start();

			include(__DIR__ . "/../index.php");

			$this->assertTrue(FALSE);

		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		$tpl_code_2 = ob_get_contents();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);	

		$this->assertTrue(
			$tpl_code_2 == ""
		);

		ob_end_clean();	
	}


	/*
	 * No test for invalid scope: This is because
	 * the OAuth server does validation of the scope
	 * (but we do validation elsewhere; but that is only
	 * for informational purposes).
	 */

	public function test_lp_login_try_state_missing() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);

		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */


			$_POST{"nonce"} = $req_nonce_string;

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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			unset($_REQUEST{"state"}); // Remove state

			ob_start();

			include(__DIR__ . "/../index.php");

			$this->assertTrue(FALSE);

		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "Invalid client settings");
		}

		$tpl_code_2 = ob_get_contents();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);	

		$this->assertTrue(
			$tpl_code_2 == ""
		);

		ob_end_clean();	
	}


	public function test_lp_login_try_missing_nonce_token() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);


		// Try another request
		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */

			unset($_POST{"nonce"});

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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code_2 = ob_get_contents();

			$this->assertContains('<form action="/" method="POST">', $tpl_code_2);
			$this->assertContains('<input type="hidden" name="response_type" value="token">', $tpl_code_2);
			$this->assertContains('<input type="hidden" name="redirect_uri" value="http://127.0.0.4/redirect_uri">', $tpl_code_2);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}


		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);	

		ob_end_clean();	
	}


	public function test_lp_login_try_invalid_nonce_token() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);


		// Try another request
		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */


			$_POST{"nonce"} = $req_nonce_string . "----"; // Make the nonce token invalid

			// Fake curl call that will always return error
			$lp_config["lp_http_curl_request_func"] = 
				"__lp_unittesting_lp_http_curl_request_fake_successful_oauth_login";

			__lp_unittesting_lp_http_curl_getinfo(FALSE, array(
				"url" => "http://127.0.0.3/oauth-login-page-grant",
				"content_type" => "application/json",
				"http_code" => 200,	// 200 HTTP code
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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			ob_start();

			include(__DIR__ . "/../index.php");

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "Nonce invalid - hash does not match");
		}

		$tpl_code_2 = ob_get_contents();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);	

			$this->assertTrue(
			$tpl_code_2 == ""
		);

	ob_end_clean();	
	}


	public function test_lp_login_try_http_200_but_no_access_token() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);


		// Try another request
		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */

			$_POST{"nonce"} = $req_nonce_string;

			// Fake curl call that will always return error
			$lp_config["lp_http_curl_request_func"] = 
				"__lp_unittesting_lp_http_curl_request_fake_failed_oauth_login";

			__lp_unittesting_lp_http_curl_getinfo(FALSE, array(
				"url" => "http://127.0.0.3/oauth-login-page-grant",
				"content_type" => "application/json",
				"http_code" => 200,	// 200 HTTP code
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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code_2 = ob_get_contents();


			$this->assertContains('Login Form Error Prefix Authentication failure. Login Form Error Suffix', $tpl_code_2);
			$this->assertContains('<form action="/" method="POST">', $tpl_code_2);
			$this->assertContains('<input type="hidden" name="response_type" value="token">', $tpl_code_2);
			$this->assertContains('<input type="hidden" name="redirect_uri" value="http://127.0.0.4/redirect_uri">', $tpl_code_2);
			$this->assertContains('<input type="submit" value="Log in">', $tpl_code_2);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}


		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);	

		ob_end_clean();	
	}


	public function test_lp_login_try_http_500() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);


		// Try another request
		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */

			$_POST{"nonce"} = $req_nonce_string;

			// Fake curl call that will always return error
			$lp_config["lp_http_curl_request_func"] = 
				"__lp_unittesting_lp_http_curl_request_fake_failed_oauth_login";

			__lp_unittesting_lp_http_curl_getinfo(FALSE, array(
				"url" => "http://127.0.0.3/oauth-login-page-grant",
				"content_type" => "application/json",
				"http_code" => 500,	// 500 error
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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code_2 = ob_get_contents();

			$this->assertContains('Could not authenticate for some reason.', $tpl_code_2);
			$this->assertContains('<form action="/" method="POST">', $tpl_code_2);
			$this->assertContains('<input type="hidden" name="response_type" value="token">', $tpl_code_2);
			$this->assertContains('<input type="hidden" name="redirect_uri" value="http://127.0.0.4/redirect_uri">', $tpl_code_2);
			$this->assertContains('<input type="submit" value="Log in">', $tpl_code_2);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}


		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);	

		ob_end_clean();
	}


	public function test_lp_login_try_http_600() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);

		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */

			$_POST{"nonce"} = $req_nonce_string;

			// Fake curl call that will always return error
			$lp_config["lp_http_curl_request_func"] = 
				"__lp_unittesting_lp_http_curl_request_fake_failed_oauth_login";

			__lp_unittesting_lp_http_curl_getinfo(FALSE, array(
				"url" => "http://127.0.0.3/oauth-login-page-grant",
				"content_type" => "application/json",
				"http_code" => 600, // Some totally weird HTTP code
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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			ob_start();

			include(__DIR__ . "/../index.php");

			$tpl_code_2 = ob_get_contents();
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "Unable to authenticate for an unknown reason.");
		}


		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);	

		ob_end_clean();
	}


	public function test_lp_login_try_user_agent_mismatch() {
		global $lp_config;
		global $_SERVER;
		global $_SESSION;

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
		$req_nonce_string = mb_substr(
			$tpl_code_nonce_index, 
			mb_strlen('<input type="hidden" name="nonce" value="'), 
			$tpl_code_nonce_ends_index - mb_strlen('<input type="hidden" name="nonce" value="')
		);

		ob_end_clean();

		// Test if only no-caching headers are in place
		$this->assertEquals(
			__lp_unittesting_header_aggregating_func(FALSE),
			$this->__nocaching_headers_prototype()
		);

		try {
			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */

			$_POST{"nonce"} = $req_nonce_string;
			$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 3.7"; // Different user-agent from before!

			// Fake curl call that will always return error
			$lp_config["lp_http_curl_request_func"] = 
				"__lp_unittesting_lp_http_curl_request_fake_failed_oauth_login";

			__lp_unittesting_lp_http_curl_getinfo(FALSE, array(
				"url" => "http://127.0.0.3/oauth-login-page-grant",
				"content_type" => "application/json",
				"http_code" => 600, // Some totally weird code
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

			__lp_unittesting_header_aggregating_func(""); // Clean out headers

			ob_start();

			include(__DIR__ . "/../index.php");

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			$this->assertContains("Cannot modify header information - headers already sent by", $e->getMessage());
		}

		ob_end_clean();
	}
}


