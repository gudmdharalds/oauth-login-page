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

		unset($lp_config);
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

			/* 
			 * Note: Although transparent, we now have 
			 * $_SESSION initialized that is being used
			 */


			$_POST{"nonce"} = $req_token;

			ob_start();

			include(__DIR__ . "/../index.php");
			
			$tpl_code_2 = ob_get_contents();

			die("tpl_code_2=$tpl_code_2");

			$this->assertTrue(
				mb_strstr($tpl_code2, "foo") !== FALSE
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

