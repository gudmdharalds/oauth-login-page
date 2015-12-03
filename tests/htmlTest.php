<?php

require_once(__DIR__ . "/../config.php");

function __lp_unittesting_html_lp_fatal_error($error_msg) {
	global $lp_unittesting_fatals;

	$lp_unittesting_fatals = TRUE;

	throw new Exception($error_msg);
}

function __lp_unittesting_html_lp_scope_info_get() {
	return (array) json_decode('{ "my-api": "My API access", "profile": "Access to profile" }');
}

function __lp_unittesting_html_time_func() {
	return 1449146192;
}

function __lp_unittesting_html_openssl_random_pseudo_bytes_func() {
	return "openssl_randomstring_butnotreally";
}

class HtmlTest extends PHPUnit_Framework_TestCase {
	public function __construct() {
		global $lp_config;

		// FIXME: Do create some random, temp file,
		// in some random folder, and use that ...
                
		$lp_config["image_page"]                        = "/static/image_page.png";
		$lp_config["image_icon"]                        = "/static/image_icon.png";
		$lp_config["page_title_prefix"]                 = "Page Title Prefix";
		$lp_config["css_file"]                          = "/static/css_file.css";
		$lp_config["login_form_heading"]                = "Login Form Heading";
		$lp_config["login_form_error_prefix"]           = "Login Form Error Prefix";
		$lp_config["login_form_error_suffix"]           = "Login Form Error Suffix";
		$lp_config["nonce_static_secret_key"]           = "Nonce_static_secRET_KEy";
		$lp_config["nonce_hashing_function"]            = "sha256";
		$lp_config["oauth2_server_access_token_uri"]    = "http://127.0.0.3/access_token";
		$lp_config["oauth2_server_scopes_info_uri"]	= "http://127.0.0.3/scopes_info";
		$lp_config["session_hashing_function"]          = "sha256";
		$lp_config["session_entropy_length"]            = "768";
		$lp_config["session_secret_function"]           = "sha256";
		$lp_config["db_driver"]                         = "sqlite3";
		$lp_config["db_name"]                           = "/tmp/sqlite3_" . time() . ".db";
		$lp_config["db_host"]                           = "-";
		$lp_config["db_user"]                           = "-";
		$lp_config["db_pass"]                           = "-";

		runkit_function_redefine("lp_fatal_error", '$error_msg', 'return __lp_unittesting_html_lp_fatal_error($error_msg);');
	}

	public function __destruct() {
		global $lp_config;

	}


	public function test_lp_tpl_output() {
		global $lp_config;


		/*
		 * Create very simple template with
		 * no fields to be replaced, but still
		 * replacement values are defined.
		 */
		
		$tmp_file_name = tempnam("/tmp", "lp_test_file_" . time() . "_");

		$this->assertTrue(
			file_put_contents($tmp_file_name,
				"<html><head><title></head><body><input type=\"hidden\" name=\"field1\" value=\"value1\">TEMPSTRING2</body></html>"
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
				$tpl_code, 
				'<html><head><title></head><body><input type="hidden" name="field1" value="value1">TEMPSTRING2</body></html>'
			);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}

		unlink($tmp_file_name);


		/*
		 * Create very simple template with
		 * no fields to be replaced, and no replacement values.
		 */
		
		$tmp_file_name = tempnam("/tmp", "lp_test_file_" . time() . "_");

		$this->assertTrue(
			file_put_contents($tmp_file_name,
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
				$tpl_code, 
				'<html><head><title></head><body><input type="hidden" name="field1" value="value1">TEMPSTRING2</body></html>'
			);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}

		unlink($tmp_file_name);



		/*
		 * Create very simple template with
		 * fields to be replaced.
		 */
		
		$tmp_file_name = tempnam("/tmp", "lp_test_file_" . time() . "_");

		$this->assertTrue(
			file_put_contents($tmp_file_name,
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
				$tpl_code, 
				'<html><head><title></head><body><input type="hidden" name="field1" value="TEMPORARYSTR1">TEMPORARYSTR2</body></html>'
			);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}

		unlink($tmp_file_name);


		/*
		 * Create very simple template with
		 * one field to be replaced.
		 */
		
		$tmp_file_name = tempnam("/tmp", "lp_test_file_" . time() . "_");

		$this->assertTrue(
			file_put_contents($tmp_file_name,
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
				$tpl_code, 
				'<html><head><title></head><body><input type="hidden" name="field1" value="TEMPORARYSTR1"></body></html>'
			);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}

		unlink($tmp_file_name);


	}


	public function test_lp_html_header_ok() {
		global $lp_config;

		try {
			ob_start();

			lp_html_header();

			$tpl_code = ob_get_contents();

			ob_end_clean();


			$this->assertFalse(
				mb_stripos($tpl_code, "<title>") === FALSE
			);

			$this->assertTrue(
				mb_stripos($tpl_code, "<body>") === FALSE
			);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}
	}


	public function test_lp_html_footer_ok() {
		global $lp_config;

		try {
			ob_start();

			lp_html_header();

			$tpl_code = ob_get_contents();

			ob_end_clean();

			$this->assertTrue(
				mb_stripos($tpl_code, "</body>") === FALSE
			);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
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

		$lp_config["time_func"] = "__lp_unittesting_html_time_func";
		$lp_config["openssl_random_pseudo_bytes_func"] = "__lp_unittesting_html_openssl_random_pseudo_bytes_func";
		runkit_function_redefine("lp_scope_info_get", '', 'return __lp_unittesting_html_lp_scope_info_get();');

		/*
		 * Same principle here.
		 */

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

			ob_end_clean();


			$this->assertTrue(
				mb_stripos($tpl_code, '<form action="/" method="POST">') !== FALSE
			);

			$this->assertTrue(
				mb_stripos($tpl_code, "</form>") !== FALSE
			);

			$this->assertTrue(
				mb_stripos($tpl_code, '<input type="hidden" name="nonce" value="b3BlbnNzbF9yYW5kb21zdHJpbmdfYnV0bm90cmVhbGx5,1449146452,8c5d085a8292dca980521d1da4e30a09633bf27193e28660e912d850bd7cabbe">') !== FALSE
			);

			$this->assertTrue(
				mb_stripos($tpl_code, '<input type="hidden" name="response_type" value="token">') !== FALSE
			);

			$this->assertTrue(
				mb_stripos($tpl_code, '<input type="hidden" name="client_id" value="testclient">') !== FALSE
			);

			$this->assertTrue(
				mb_stripos($tpl_code, '<input type="hidden" name="redirect_uri" value="http://127.0.0.1/redirect_endpoint">') !== FALSE
			);

			$this->assertTrue(
				mb_stripos($tpl_code, '<input type="hidden" name="scope" value="my-api">') !== FALSE
			);

			$this->assertTrue(
				mb_stripos($tpl_code, '<input type="hidden" name="state" value="some_state">') !== FALSE
			);
		}

		catch (Exception $e) {
			$this->assertEquals($e->getMessage(), "");
		}
	}


	/*
	 * No test for lp_fatal_error() as that would exit 
	 * the test-suite. 
	 */

}



