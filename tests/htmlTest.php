<?php

require_once(__DIR__ . "/../config.php");

function __lp_unittesting_html_lp_fatal_error($error_msg) {
	global $lp_unittesting_fatals;

	$lp_unittesting_fatals = TRUE;

	throw new Exception($error_msg);
}

class HtmlTest extends PHPUnit_Framework_TestCase {
	public function __construct() {
		global $lp_config;

		// FIXME: Do create some random, temp file,
		// in some random folder, and use that ...

		lp_config();

		runkit_function_redefine("lp_fatal_error", '$error_msg', 'return __lp_unittesting_html_lp_fatal_error($error_msg);');
	}

	public function __destruct() {
		global $lp_config;

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


	// FIXME: Test lp_login_form()



}



