<?php

require_once(__DIR__ . "/../config.php");

function __lp_unittesting_lp_fatal_error($error_msg) {
	global $lp_unittesting_fatals;

	$lp_unittesting_fatals = TRUE;

	throw new Exception($error_msg);
}

function __lp_unittesting_session_start() {
	global $_SESSION;

	return TRUE;
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
		global $_SERVER;
		global $_SESSION;

		/*
		 * Use our own session_start() function (see above).
		 * Note that a session will not be really started,
		 * but we can nevertheless test what $_SESSION contains.
		 *
		 *
		 * Specify our own hashing function and entropy length -
		 * will be set with ini_set().
	 	 */

		$lp_config["session_start_func"] = '__lp_unittesting_session_start';
		$lp_config["session_hashing_function"] = "sha1";
		$lp_config["session_entropy_length"] = 136;

		ini_set('track_errors', 1);

		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/1.0';


		try { 	
			lp_session_init();
		}

		catch (Exception $e) {
			$fatal_error = $e->getMessage();

			$this->assertEquals($fatal_error, "");
		}

		/*
	 	 * The session secret generated
		 * and then validate it ...
		 */
		
		$lp_nonce_session_secret = $_SESSION["lp_nonce_session_secret"];

		$this->assertTrue(
			is_string($lp_nonce_session_secret)
		);

		$this->assertTrue(
			strlen($lp_nonce_session_secret) > 0
		);


		/* 
		 * Check if all config variables were set
		 */

		$this->assertEquals(
			ini_get('session.use_trans_sid'),
			0
		);

		$this->assertEquals(
			ini_get('session.use_only_cookies'),
			1
		);

		/*
		 * This setting is only supported in versions 5.5.2 or newer.
		 */
		        
		if (version_compare(PHP_VERSION, '5.5.2', '>=') === TRUE) {
			$this->assertEquals(
				ini_get('session.use_strict_mode'),
				1
			);
		}

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
			136
		);


		$this->assertEquals(
			session_name(),
			"LP_SESSION"
		);


		/*
		 * Fake another request - all should
		 * be fine, as the 'User-agent' is the same ...
		 */

		try {
			lp_session_init();
		}

		catch (Exception $e) {
			$this->assertEqual("", $e->getMessage());
		}

		$this->assertEquals($lp_nonce_session_secret, $_SESSION["lp_nonce_session_secret"]);


		/* 
		 * Fake yet another request, but with
		 * a different user-agent string - now
	 	 * things should hit the fan.
		 */

		$_SERVER["HTTP_USER_AGENT"] = "Internet Explorer 3.0";

		try {
			lp_session_init();
		}

		catch (Exception $e) {
			/*
			 * This should happen when setcookie() is called
			 * by lp_session_init() -- and so we assume things
			 * are just fine.
			 */

			$this->assertContains("Cannot modify header information - headers already sent by", $e->getMessage());
		}
	
		$this->assertEquals($lp_nonce_session_secret, $_SESSION["lp_nonce_session_secret"]);	
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
