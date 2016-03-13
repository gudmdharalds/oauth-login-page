<?php


require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../includes.php");
require_once(__DIR__ . "/shared.php");


class HtmlTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		PHPUnit_Framework_Error_Notice::$enabled = TRUE;

		global $lp_config;

		$lp_config = __lp__unittesting_lp_config_fake();

		$lp_config["lp_scope_info_get_func"] = "__lp_unittesting_html_lp_scope_info_get_success";


		// Create snapshot
		__lp__unittesting_superglobals_snapshot(TRUE);
	}


	public function tearDown() {
		unset($lp_config);

		__lp_unittesting_lp_config_cleanups();

		// Put snapshot in place
		__lp__unittesting_superglobals_snapshot(FALSE);
	}


	private function tpl_tmp_dir_prepare() {
		$this->lp_tpl_dir_real = getcwd();

		$this->assertNotFalse($this->lp_tpl_dir_real);

		$this->lp_tpl_base_dir_tmp = "/tmp/lp_tpl_test_dir_" .
			md5('' . mt_rand());

		$this->lp_tpl_templates_dir_tmp =
			$this->lp_tpl_base_dir_tmp . "/tpl";


		$this->assertTrue(
			mkdir($this->lp_tpl_base_dir_tmp, 0700)
		);

		$this->assertTrue(
			chdir($this->lp_tpl_base_dir_tmp)
		);

		$this->assertTrue(
			mkdir("tpl", 0700)
		);
	}

	private function tpl_tmp_dir_cleanup() {
		$this->assertTrue(
			chdir($this->lp_tpl_dir_real)
		);

		/*
		 * Actually remove the directories
		 * -- these should be empty by now,
		 * as everything the individual tests	
		  create, they delete after usage.
		 */

		$this->assertTrue(
			rmdir($this->lp_tpl_templates_dir_tmp)
		);

		$this->assertTrue(
			rmdir($this->lp_tpl_base_dir_tmp)
		);

	}


	private function rand_str() {
		return md5((string) mt_rand());
	}

	public function test_lp_tpl_output1() {
		global $lp_config;

		$this->tpl_tmp_dir_prepare();

		/*
		 * Create very simple template with
		 * no fields to be replaced, but still
		 * replacement values are defined.
		 */

		$tmp_file_name = "lp_test_file_" . $this->rand_str() . "";

		$tmp_file_path =
			$this->lp_tpl_templates_dir_tmp . "/" .
			$tmp_file_name;

		$tmp_file_path_real = $tmp_file_path . ".tpl.php";

		$this->assertTrue(
			file_put_contents(
				$tmp_file_path_real,
				"<html><head><title></head><body><input type=\"hidden\" name=\"field1\" value=\"value1\">TEMPSTRING2</body></html>"
			) !== FALSE
		);


		try {
			ob_start();

			// Render template
			lp_tpl_output(
				array(
					"%tmpstr1%" => "TEMPORARYSTR1",
					"%tmpstr2%" => "TEMPORARYSTR2"
				),
				$tmp_file_name
			);


			$tpl_code = ob_get_contents();

			ob_end_clean();

			// Test if rendering succeeded.
			$this->assertEquals(
				'<html><head><title></head><body><input type="hidden" name="field1" value="value1">TEMPSTRING2</body></html>',
				$tpl_code
			);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"",
				$e->getMessage()
			);
		}

		unlink($tmp_file_path_real); // Remove template file

		$this->tpl_tmp_dir_cleanup();
	}


	public function test_lp_tpl_output2() {
                global $lp_config;

		$this->tpl_tmp_dir_prepare();


		/*
		 * Create very simple template with
		 * no fields to be replaced, and no replacement values.
		 */

		$tmp_file_name = "lp_test_file_" . $this->rand_str();

		$tmp_file_path =
			$this->lp_tpl_templates_dir_tmp . "/" .
			$tmp_file_name;

		$tmp_file_path_real = $tmp_file_path . ".tpl.php";


		$this->assertTrue(
			file_put_contents($tmp_file_path_real,
				"<html><head><title></head><body><input type=\"hidden\" name=\"field1\" value=\"value1\">TEMPSTRING2</body></html>"
			) !== FALSE
		);


		try {
			ob_start();

			// Render template
			lp_tpl_output(array(), $tmp_file_name);


			$tpl_code = ob_get_contents();

			ob_end_clean();

			// Test if rendering succeeded.
			$this->assertEquals(
				'<html><head><title></head><body><input type="hidden" name="field1" value="value1">TEMPSTRING2</body></html>',
				$tpl_code
			);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"",
				$e->getMessage()
			);
		}

		unlink($tmp_file_path_real);

		$this->tpl_tmp_dir_cleanup();
	}

	public function test_lp_tpl_output3() {
                global $lp_config;

		$this->tpl_tmp_dir_prepare();

		/*
		 * Create very simple template with
		 * fields to be replaced.
		 */

		$tmp_file_name = "lp_test_file_" . $this->rand_str();

		$tmp_file_path =
			$this->lp_tpl_templates_dir_tmp . "/" .
			$tmp_file_name;

		$tmp_file_path_real = $tmp_file_path . ".tpl.php";


		$this->assertTrue(
			file_put_contents($tmp_file_path_real,
				"<html><head><title></head><body><input type=\"hidden\" name=\"field1\" value=\"%tmpstr1%\">%tmpstr2%</body></html>"
			) !== FALSE
		);


		try {
			ob_start();

			// Render template
			lp_tpl_output(array(
					"%tmpstr1%" => "TEMPORARYSTR1",
					"%tmpstr2%" => "TEMPORARYSTR2"
			), $tmp_file_name);


			$tpl_code = ob_get_contents();

			ob_end_clean();

			// Test if rendering succeeded.
			$this->assertEquals(
				'<html><head><title></head><body><input type="hidden" name="field1" value="TEMPORARYSTR1">TEMPORARYSTR2</body></html>',
				$tpl_code
			);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"",
				$e->getMessage()
			);
		}

		unlink($tmp_file_path_real);

		$this->tpl_tmp_dir_cleanup();
	}


	public function test_lp_tpl_output4() {
                global $lp_config;

		$this->tpl_tmp_dir_prepare();

		/*
		 * Create very simple template with
		 * one field to be replaced.
		 */

		$tmp_file_name = "lp_test_file_" . $this->rand_str();

		$tmp_file_path =
			$this->lp_tpl_templates_dir_tmp . "/" .
			$tmp_file_name;

		$tmp_file_path_real = $tmp_file_path . ".tpl.php";


		$this->assertTrue(
			file_put_contents($tmp_file_path_real,
				"<html><head><title></head><body><input type=\"hidden\" name=\"field1\" value=\"%tmpstr1%\"></body></html>"
			) !== FALSE
		);


		try {
			ob_start();

			// Render template
			lp_tpl_output(array(
					"%tmpstr1%" => "TEMPORARYSTR1",
			), $tmp_file_name);


			$tpl_code = ob_get_contents();

			ob_end_clean();

			// Test if rendering succeeded.
			$this->assertEquals(
				'<html><head><title></head><body><input type="hidden" name="field1" value="TEMPORARYSTR1"></body></html>',
				$tpl_code
			);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"",
				$e->getMessage()
			);
		}

		unlink($tmp_file_path_real);


		$this->tpl_tmp_dir_cleanup();
	}


	public function test_lp_tpl_output5() {
                global $lp_config;

		$this->tpl_tmp_dir_prepare();

		/*
		 * Create very simple template with
		 * one field to be replaced.
		 */

		$tmp_file_name = "lp_test_file_" . $this->rand_str();

		$tmp_file_path =
			$this->lp_tpl_templates_dir_tmp . "/" .
			$tmp_file_name;

		/* 
		 * -default.tpl.php suffix -- test
		 * if lp_tpl_output() detects such files.
		 */

		$tmp_file_path_real = $tmp_file_path . "-default.tpl.php";


		$this->assertTrue(
			file_put_contents($tmp_file_path_real,
				"<html><head><title></head><body><input type=\"hidden\" name=\"field1\" value=\"%tmpstr1%\"></body></html>"
			) !== FALSE
		);


		try {
			ob_start();

			// Render template
			lp_tpl_output(array(
					"%tmpstr1%" => "TEMPORARYSTR1",
			), $tmp_file_name);


			$tpl_code = ob_get_contents();

			ob_end_clean();

			// Test if rendering succeeded.
			$this->assertEquals(
				'<html><head><title></head><body><input type="hidden" name="field1" value="TEMPORARYSTR1"></body></html>',
				$tpl_code
			);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"",
				$e->getMessage()
			);
		}

		unlink($tmp_file_path_real);


		$this->tpl_tmp_dir_cleanup();
	}

	public function test_lp_html_header_ok() {
		global $lp_config;

		try {
			ob_start();

			lp_html_header();

			$tpl_code = ob_get_contents();

			ob_end_clean();


			$this->assertTrue(
				mb_strstr($tpl_code, "<title>") !== FALSE
			);

			$this->assertTrue(
				mb_strstr($tpl_code, "<body ") !== FALSE
			);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"",
				$e->getMessage()
			);
		}
	}


	public function test_lp_html_footer_ok() {
		global $lp_config;

		try {
			ob_start();

			lp_html_footer();

			$tpl_code = ob_get_contents();

			ob_end_clean();

			$this->assertTrue(
				mb_strstr($tpl_code, "</body>") !== FALSE
			);

			$this->assertTrue(
				mb_strstr($tpl_code, "</html>") !== FALSE
			);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"",
				$e->getMessage()
			);
		}
	}


	public function test_lp_login_form() {
		global $lp_config;
		global $_SESSION;


		/*
		 * These overridden functions will return constant values
		 * -- we do this so that we can test if the template-rendering
		 * really produces accurate <input>-fields. No guessing, no 'circa'-correct, but
		 * absolutely certain that it does.
		 */

		$lp_config["time_func"] = "__lp_unittesting_html_time_static_func";
		$lp_config["openssl_random_pseudo_bytes_func"] = "__lp_unittesting_html_openssl_random_pseudo_bytes_static_func";

		/*
		 * Same principle here.
		 */

		$_SESSION{"lp_nonce_session_secret"} = "mega_session_secret_butnotreally";
		$lp_config{"nonce_static_secret_key"} = "mega_static_secret_butnotreally";
		$lp_config{"nonce_hashing_function"} = "sha256";

		$lp_config["lp_scope_info_get_func"] = "__lp_unittesting_html_lp_scope_info_get_success";


		$_REQUEST{"scope"}		= "my-api";
		$_REQUEST{"client_id"}		= "testclient";
		$_REQUEST{"redirect_uri"}	= "http://127.0.0.1/redirect_endpoint";
		$_REQUEST{"response_type"}	= "token";
		$_REQUEST{"state"}		= "some_state";



		try {
			ob_start();

			lp_login_form();

			$tpl_code = ob_get_contents();

			ob_end_clean();


			$this->assertTrue(
				mb_strstr($tpl_code, '<form action="/" method="POST">') !== FALSE
			);

			$this->assertTrue(
				mb_strstr($tpl_code, "</form>") !== FALSE
			);

			$this->assertTrue(
				mb_strstr($tpl_code, '<input type="hidden" name="nonce" value="b3BlbnNzbF9yYW5kb21zdHJpbmdfYnV0bm90cmVhbGx5,1449146452,8c5d085a8292dca980521d1da4e30a09633bf27193e28660e912d850bd7cabbe">') !== FALSE
			);

			$this->assertTrue(
				mb_strstr($tpl_code, '<input type="hidden" name="response_type" value="token">') !== FALSE
			);

			$this->assertTrue(
				mb_strstr($tpl_code, '<input type="hidden" name="client_id" value="testclient">') !== FALSE
			);

			$this->assertTrue(
				mb_strstr($tpl_code, '<input type="hidden" name="redirect_uri" value="http://127.0.0.1/redirect_endpoint">') !== FALSE
			);

			$this->assertTrue(
				mb_strstr($tpl_code, '<input type="hidden" name="scope" value="my-api">') !== FALSE
			);

			$this->assertTrue(
				mb_strstr($tpl_code, '<input type="hidden" name="state" value="some_state">') !== FALSE
			);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"",
				$e->getMessage()
			);
		}
	}


	public function test_lp_login_form_fail_session_secrets() {
		global $lp_config;
		global $_SESSION;


		$lp_config["time_func"] = "__lp_unittesting_html_time_static_func";
		$lp_config["openssl_random_pseudo_bytes_func"] = "__lp_unittesting_html_openssl_random_pseudo_bytes_static_func";
		unset($_SESSION{"lp_nonce_session_secret"}); // Remove this to ensure failure


		$lp_config{"nonce_static_secret_key"} = "mega_static_secret_butnotreally";
		$lp_config{"nonce_hashing_function"} = "sha256";


		$_REQUEST{"scope"}		= "my-api";
		$_REQUEST{"client_id"}		= "testclient";
		$_REQUEST{"redirect_uri"}	= "http://127.0.0.1/redirect_endpoint";
		$_REQUEST{"response_type"}	= "token";
		$_REQUEST{"state"}		= "some_state";


		try {
			ob_start();

			lp_login_form();

			$tpl_code = ob_get_contents();

			ob_end_clean();

			$this->assertFalse(TRUE); // This should never be run; exception should occur.
		}

		catch (Exception $e) {
			$this->assertEquals(
				"Session secret not defined!",
				$e->getMessage()
			);

			ob_end_clean();
		}
	}


	public function test_lp_login_form_fail_static_secrets() {
		global $lp_config;
		global $_SESSION;


		$lp_config["time_func"] = "__lp_unittesting_html_time_static_func";
		$lp_config["openssl_random_pseudo_bytes_func"] = "__lp_unittesting_html_openssl_random_pseudo_bytes_static_func";


		$_SESSION{"lp_nonce_session_secret"} = "mega_session_secret_butnotreally";
		unset($lp_config{"nonce_static_secret_key"}); // Remove to ensure failure

		$lp_config{"nonce_hashing_function"} = "sha256";


		$_REQUEST{"scope"}		= "my-api";
		$_REQUEST{"client_id"}		= "testclient";
		$_REQUEST{"redirect_uri"}	= "http://127.0.0.1/redirect_endpoint";
		$_REQUEST{"response_type"}	= "token";
		$_REQUEST{"state"}		= "some_state";


		try {
			ob_start();

			lp_login_form();

			$tpl_code = ob_get_contents();

			ob_end_clean();

			$this->assertFalse(TRUE);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"Undefined index: nonce_static_secret_key",
				$e->getMessage()
			);

			ob_end_clean();
		}
	}


	public function test_lp_login_form_fail_scope_info_get() {
		global $lp_config;
		global $_SESSION;


		$lp_config["time_func"] = "__lp_unittesting_html_time_static_func";
		$lp_config["openssl_random_pseudo_bytes_func"] = "__lp_unittesting_html_openssl_random_pseudo_bytes_static_func";

		$lp_config["lp_scope_info_get_func"] = "__lp_unittesting_html_lp_scope_info_get_error";


		$_SESSION{"lp_nonce_session_secret"} = "mega_session_secret_butnotreally";
		$lp_config{"nonce_static_secret_key"} = "mega_static_secret_butnotreally";

		$lp_config{"nonce_hashing_function"} = "sha256";


		$_REQUEST{"scope"}		= "my-api";
		$_REQUEST{"client_id"}		= "testclient";
		$_REQUEST{"redirect_uri"}	= "http://127.0.0.1/redirect_endpoint";
		$_REQUEST{"response_type"}	= "token";
		$_REQUEST{"state"}		= "some_state";


		try {
			ob_start();

			lp_login_form();

			$tpl_code = ob_get_contents();

			$this->assertTrue(FALSE);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"Unable to get information about scopes: stat(): stat failed for tpl/footer.tpl.php",
				$e->getMessage()
			);
		}

		ob_end_clean();
	}


	public function test_lp_login_form_fail_scope_not_found() {
		global $lp_config;
		global $_SESSION;


		$lp_config["time_func"] = "__lp_unittesting_html_time_static_func";
		$lp_config["openssl_random_pseudo_bytes_func"] = "__lp_unittesting_html_openssl_random_pseudo_bytes_static_func";


		$_SESSION{"lp_nonce_session_secret"} = "mega_session_secret_butnotreally";
		$lp_config{"nonce_static_secret_key"} = "mega_static_secret_butnotreally";

		$lp_config{"nonce_hashing_function"} = "sha256";
		$lp_config["lp_scope_info_get_func"] = "__lp_unittesting_html_lp_scope_info_get_success";


		$_REQUEST{"scope"}		= "SOMETHINGTOTALLYINVALID"; // Invalid scope
		$_REQUEST{"client_id"}		= "testclient";
		$_REQUEST{"redirect_uri"}	= "http://127.0.0.1/redirect_endpoint";
		$_REQUEST{"response_type"}	= "token";
		$_REQUEST{"state"}		= "some_state";


		try {
			ob_start();

			lp_login_form();

			$tpl_code = ob_get_contents();

			$this->assertFalse(TRUE);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"Could not get information about requested scope",
				$e->getMessage()
			);
		}

		ob_end_clean();
	}


	public function test_lp_login_form_fail_scope_not_found2() {
		global $lp_config;
		global $_SESSION;


		$lp_config["time_func"] = "__lp_unittesting_html_time_static_func";
		$lp_config["openssl_random_pseudo_bytes_func"] = "__lp_unittesting_html_openssl_random_pseudo_bytes_static_func";


		$_SESSION{"lp_nonce_session_secret"} = "mega_session_secret_butnotreally";
		$lp_config{"nonce_static_secret_key"} = "mega_static_secret_butnotreally";

		$lp_config{"nonce_hashing_function"} = "sha256";
		$lp_config["lp_scope_info_get_func"] = "__lp_unittesting_html_lp_scope_info_get_success";


		$_REQUEST{"scope"}		= ""; // Invalid scope
		$_REQUEST{"client_id"}		= "testclient";
		$_REQUEST{"redirect_uri"}	= "http://127.0.0.1/redirect_endpoint";
		$_REQUEST{"response_type"}	= "token";
		$_REQUEST{"state"}		= "some_state";


		try {
			ob_start();

			lp_login_form();

			$tpl_code = ob_get_contents();

			$this->assertFalse(TRUE);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"Could not get information about requested scope",
				$e->getMessage()
			);
		}

		ob_end_clean();
	}


	public function test_lp_login_form_fail_redirect_uri_invalid() {
		global $lp_config;
		global $_SESSION;


		$lp_config["time_func"] = "__lp_unittesting_html_time_static_func";
		$lp_config["openssl_random_pseudo_bytes_func"] = "__lp_unittesting_html_openssl_random_pseudo_bytes_static_func";



		$_SESSION{"lp_nonce_session_secret"} = "mega_session_secret_butnotreally";
		$lp_config{"nonce_static_secret_key"} = "mega_static_secret_butnotreally";

		$lp_config{"nonce_hashing_function"} = "sha256";

		$lp_config["lp_scope_info_get_func"] = "__lp_unittesting_html_lp_scope_info_get_success";

		$_REQUEST{"scope"}		= "my-api";
		$_REQUEST{"client_id"}		= "testclient";
		$_REQUEST{"redirect_uri"}	= "asdf"; // Invalid redirect URI
		$_REQUEST{"response_type"}	= "token";
		$_REQUEST{"state"}		= "some_state";


		try {
			ob_start();

			lp_login_form();

			$tpl_code = ob_get_contents();

			$this->assertFalse(TRUE);
		}

		catch (Exception $e) {
			$this->assertEquals(
				"Redirect URI is illegal",
				$e->getMessage()
			);
		}

		ob_end_clean();
	}


	/*
	 * No test for lp_fatal_error() as that would exit
	 * the test-suite.
	 */

}



