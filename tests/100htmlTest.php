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

	public function test_lp_init_check_ok() {
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

}

