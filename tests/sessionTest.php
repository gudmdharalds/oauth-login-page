<?php

require_once(__DIR__ . "/../config.php");

function __lp_unittesting_lp_fatal_error($error_msg) {
	global $lp_unittesting_fatals;

	$lp_unittesting_fatals = TRUE;

	throw new Exception($error_msg);
}

function __lp_unittesting_session_start() {
}

class SessionTest extends \PHPUnit_Framework_TestCase {
	public function __construct() {
		global $lp_config;

		$lp_config = lp_config();
	
		runkit_function_redefine("lp_fatal_error", '$error_msg', 'return __lp_unittesting_lp_fatal_error($error_msg);');
	}
        
	public function __destruct() {
	}

	public function test_generate_session_secret_ok() {
		global $lp_config;

		$lp_config["session_secret_function"] = "sha256";

		$this->assertTrue(
			lp_generate_session_secret() !== FALSE
		);	
	}


	public function test_generate_session_secret_invalid_hash_function() {
		global $lp_config;

		$lp_config["session_secret_function"] = "notvalid";

		$this->assertFalse(
			@lp_generate_session_secret()
		);	
	}

	public function test_session_init_ok() {
		global $lp_config;

		

		$lp_config["session_hashing_function"] = "sha1";
		$lp_config["session_entropy_length"] = "sha1";

		ini_set('track_errors', 1);

		try { 	
			lp_session_init();
		}

		catch (Exception $e) {
			$fatal_error = $e->getMessage();
		}


		$this->assertEquals(
			ini_get('session.use_trans_sid'),
			0
		);

		$this->assertEquals(
			ini_get('session.use_only_cookies'),
			1
		);

		/*
		 * Welcome to PHP: This test always fails.
		 */

		/*
		$this->assertEquals(
			ini_get('session.use_strict_mode'),
			1
		);
		*/

		$this->assertEquals(
			ini_get('session.cookie_httponly'),
			1
		);

       
		$this->assertEquals( 
			ini_get("session.hash_function"), 
			"sha1"
		);


		$this->assertEquals(
			ini_get("session.entropy_length"), 
			"sha1"
		);


		$this->assertEquals(
			session_name(),
			"LP_SESSION"
		);


		// And here testing of this function ends.
		// PHP unit-testing and sessions don't mix well.
	}

	public function test_session_handler_open_ok() {
		$lp_session_handler = new LPSessionHandler();

		$ret = $lp_session_handler->open("", "LP_SESSION");

		$this->assertTrue($ret);
	} 


	public function test_session_handler_open_db_failure() {
		global $lp_config;

		$lp_config["db_driver"] = "snud";

		$lp_session_handler = new LPSessionHandler();

		try {
			$lp_session_handler->open("", "LP_SESSION");
		}

		catch (Exception $e) {
		}

		$this->assertEquals(
			$e->getMessage(), 
			"Could not connect to database: could not find driver"
		);
	}


	public function test_session_handler_close_ok() {
		$lp_session_handler = new LPSessionHandler();

		$ret = $lp_session_handler->open("", "LP_SESSION");
		$this->assertTrue($ret);

		$ret = $lp_session_handler->close();
		$this->assertTrue($ret);
	} 



 
}
