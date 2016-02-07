<?php

require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../includes.php");
require_once(__DIR__ . "/shared.php");


class SessionTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		global $lp_config;

		/*
		 * Use real, user-configured settings, as we 
		 * want to run the session-tests on a 
		 * DB engine that the user is using.
		 *
		 * Note that the DB used is different from 
		 * non-testing.
		 */
		
		$lp_config = lp_config_real();

	
		ini_set('session.gc_maxlifetime', 1000);
		$lp_config['openssl_random_pseudo_bytes_func'] = 'openssl_random_pseudo_bytes';
		$lp_config['time_func'] = 'time';


		// Create DB-table
		__lp__unittesting_lp_db_test_prepare();

		PHPUnit_Framework_Error_Notice::$enabled = TRUE;

		                
		// Save snapshot
		__lp__unittesting_superglobals_snapshot(TRUE);
	}
        
	public function tearDown() {
		global $lp_config;

		unset($lp_config);

		// Put snapshot in place
		__lp__unittesting_superglobals_snapshot(FALSE);        
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

		$lp_config["session_start_func"] = '__lp_unittesting_session_static_start';
		$lp_config["session_hashing_function"] = "sha256";
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
			"sha256"
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

			$this->assertFalse(TRUE); // Should not run; exception should occur
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


	/*
	 * Test LPSessionHandler class
	 */

	public function test_session_handler_open_ok() {
		$lp_session_handler = new LPSessionHandler();

		$ret = $lp_session_handler->open("", "LP_SESSION");

		$this->assertTrue($ret);
	} 


	public function test_session_handler_open_db_failure() {
		global $lp_config;

		$lp_config["db_dsn"] = "snud";

		$lp_session_handler = new LPSessionHandler();

		try {
			$lp_session_handler->open("", "LP_SESSION");
		}

		catch (Exception $e) {
		}

		$this->assertEquals(
			$e->getMessage(), 
			"Could not connect to database: invalid data source name"
		);
	}


	/**
	 * @depends test_session_handler_open_ok
	 */

	public function test_session_handler_write_ok() {
		$lp_session_handler = new LPSessionHandler();

		$ret = $lp_session_handler->open("", "LP_SESSION");
		$this->assertTrue($ret);

		// Write once ...
		$this->assertTrue(
			$lp_session_handler->write("someSessionID", 
				json_encode(array("data1" => "one", "data2" => "two"))
			)
		);

		// Check if it really worked
		$db_conn = lp_db_pdo_init();

		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions ORDER BY session_expires ASC");
		$db_res = $db_stmt->fetchAll();

		$this->assertCount(1, $db_res);

		$this->assertTrue(is_string($db_res[0]["session_id"]));
		$this->assertTrue(strlen($db_res[0]["session_id"]) > 0);
		$this->assertEquals($db_res[0]["session_id"], "someSessionID");

		$this->assertTrue(is_string($db_res[0]["session_expires"]));
		$this->assertTrue(strlen($db_res[0]["session_expires"]) > 0);
		$this->assertTrue(is_numeric($db_res[0]["session_expires"]));
		$this->assertTrue($db_res[0]["session_expires"] > 0);

		$this->assertTrue(is_string($db_res[0]["session_data"]));
		$this->assertTrue(strlen($db_res[0]["session_data"]) > 0);

		$session_data = json_decode($db_res[0]["session_data"]);
		$this->assertTrue($session_data !== TRUE);

		$session_data = (array) $session_data;
		$this->assertEquals($session_data["data1"], "one");
		$this->assertEquals($session_data["data2"], "two");
		$this->assertCount(2, $session_data);


		// Write again ...
		$this->assertTrue(
			$lp_session_handler->write("someSessionID", 
				json_encode(array("data3" => "three", "data4" => "four"))
			)
		);


		// Check if it really worked
		$db_conn = lp_db_pdo_init();

		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions ORDER BY session_expires ASC");
		$db_res = $db_stmt->fetchAll();

		$this->assertCount(1, $db_res);

		$this->assertTrue(is_string($db_res[0]["session_id"]));
		$this->assertTrue(strlen($db_res[0]["session_id"]) > 0);
		$this->assertEquals($db_res[0]["session_id"], "someSessionID");

		$this->assertTrue(is_string($db_res[0]["session_expires"]));
		$this->assertTrue(strlen($db_res[0]["session_expires"]) > 0);
		$this->assertTrue(is_numeric($db_res[0]["session_expires"]));
		$this->assertTrue($db_res[0]["session_expires"] > 0);

		$this->assertTrue(is_string($db_res[0]["session_data"]));
		$this->assertTrue(strlen($db_res[0]["session_data"]) > 0);

		$session_data = json_decode($db_res[0]["session_data"]);
		$this->assertTrue($session_data !== TRUE);

		$session_data = (array) $session_data;
		$this->assertEquals($session_data["data3"], "three");
		$this->assertEquals($session_data["data4"], "four");
		$this->assertCount(2, $session_data);	// There should only be two keys


		/*
		 * Now add something for a different session...
		 */

		sleep(2);	// To make sure that session_expires for the new record will be later in time...

		// Write again ...
		$this->assertTrue(
			$lp_session_handler->write("someSessionIDTwo", 
				json_encode(array("otherdata_a" => "bla", "otherdata_b" => "bleh"))
			)
		);

	
		// Check if it really worked
		$db_conn = lp_db_pdo_init();

		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions ORDER BY session_expires ASC");
		$db_res = $db_stmt->fetchAll();

		// First check the 'second' session ...
		$this->assertCount(2, $db_res);

		$this->assertTrue(is_string($db_res[0]["session_id"]));
		$this->assertTrue(strlen($db_res[0]["session_id"]) > 0);
		$this->assertEquals($db_res[0]["session_id"], "someSessionID");

		$this->assertTrue(is_string($db_res[0]["session_expires"]));
		$this->assertTrue(strlen($db_res[0]["session_expires"]) > 0);
		$this->assertTrue(is_numeric($db_res[0]["session_expires"]));

		$this->assertTrue(is_string($db_res[0]["session_data"]));
		$this->assertTrue(strlen($db_res[0]["session_data"]) > 0);

		$session_data = json_decode($db_res[0]["session_data"]);
		$this->assertTrue($session_data !== TRUE);

		$session_data = (array) $session_data;
		$this->assertEquals($session_data["data3"], "three");
		$this->assertEquals($session_data["data4"], "four");
		$this->assertCount(2, $session_data);


		$this->assertTrue(is_string($db_res[1]["session_id"]));
		$this->assertTrue(strlen($db_res[1]["session_id"]) > 0);
		$this->assertEquals($db_res[1]["session_id"], "someSessionIDTwo");

		$this->assertTrue(is_string($db_res[1]["session_expires"]));
		$this->assertTrue(strlen($db_res[1]["session_expires"]) > 0);
		$this->assertTrue(is_numeric($db_res[1]["session_expires"]));

		$this->assertTrue(is_string($db_res[1]["session_data"]));
		$this->assertTrue(strlen($db_res[1]["session_data"]) > 0);

		$session_data = json_decode($db_res[1]["session_data"]);
		$this->assertTrue($session_data !== TRUE);

		$session_data = (array) $session_data;
		$this->assertEquals($session_data["otherdata_a"], "bla");
		$this->assertEquals($session_data["otherdata_b"], "bleh");
		$this->assertCount(2, $session_data);


		/*
		 * Make sure that the two records are totally dissimilar,
		 * as they should (for the session_data is dissimilar, and expiry-times
		 * should be different reflecting dissimilar creation times, plus that ID is is different),
		 * By doing this we make sure that any possible UPDATE isn't overwriting data.
		 */

		$this->assertNotEquals($db_res[0]["session_id"], $db_res[1]["session_id"]);
		$this->assertNotEquals($db_res[0]["session_expires"], $db_res[1]["session_expires"]);
		$this->assertNotEquals($db_res[0]["session_data"], $db_res[1]["session_data"]);

		sleep(2);
	
		/*
		 * Try updating the earlier session,
		 * then check if it succeeded and that 
		 * the other record is fine.
		 */
                
		// Write again ...
		$this->assertTrue(
			$lp_session_handler->write("someSessionID",
				json_encode(array("forsomethingtotallydifferent1" => "1", "elsedifferent2" => "2"))
			)
		);

		// Check if it really worked
		$db_conn = lp_db_pdo_init();

		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions ORDER BY session_expires ASC");
		$db_res = $db_stmt->fetchAll();

		$this->assertCount(2, $db_res);

		$this->assertTrue(is_string($db_res[0]["session_id"]));
		$this->assertTrue(strlen($db_res[0]["session_id"]) > 0);

		$this->assertTrue(is_string($db_res[0]["session_expires"]));
		$this->assertTrue(strlen($db_res[0]["session_expires"]) > 0);
		$this->assertTrue(is_numeric($db_res[0]["session_expires"]));

		$this->assertTrue(is_string($db_res[0]["session_data"]));
		$this->assertTrue(strlen($db_res[0]["session_data"]) > 0);

		$session_data = json_decode($db_res[0]["session_data"]);
		$this->assertTrue($session_data !== TRUE);

		$session_data = (array) $session_data;
		$this->assertEquals($session_data["otherdata_a"], "bla");
		$this->assertEquals($session_data["otherdata_b"], "bleh");
		$this->assertCount(2, $session_data);


		$this->assertTrue(is_string($db_res[1]["session_id"]));
		$this->assertTrue(strlen($db_res[1]["session_id"]) > 0);

		$this->assertTrue(is_string($db_res[1]["session_expires"]));
		$this->assertTrue(strlen($db_res[1]["session_expires"]) > 0);
		$this->assertTrue(is_numeric($db_res[1]["session_expires"]));

		$this->assertTrue(is_string($db_res[1]["session_data"]));
		$this->assertTrue(strlen($db_res[1]["session_data"]) > 0);

		$session_data = json_decode($db_res[1]["session_data"]);
		$this->assertTrue($session_data !== TRUE);

		$session_data = (array) $session_data;
		$this->assertEquals($session_data["forsomethingtotallydifferent1"], "1");
		$this->assertEquals($session_data["elsedifferent2"], "2");
		$this->assertCount(2, $session_data);

                
		$this->assertNotEquals($db_res[0]["session_id"], $db_res[1]["session_id"]);
		$this->assertNotEquals($db_res[0]["session_expires"], $db_res[1]["session_expires"]);
		$this->assertNotEquals($db_res[0]["session_data"], $db_res[1]["session_data"]);

		$lp_session_handler->destroy("someSessionID");
		$lp_session_handler->destroy("someSessionIDTwo");

		$db_conn = NULL;
	}


	/** 
	 * @depends test_session_handler_write_ok 
	 */
	
 	public function test_session_handler_read_ok() {	
		$lp_session_handler = new LPSessionHandler();

		$ret = $lp_session_handler->open("", "LP_SESSION");
		$this->assertTrue($ret);

		$lp_session_handler->write("someSessionID", json_encode(array("datareadok1" => "ONCE", "datareadok2" => "TWICE")));	

		// Check if it really worked
		$db_conn = lp_db_pdo_init();

		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions");
		$db_res = $db_stmt->fetchAll();

		$this->assertCount(1, $db_res);


		$this->assertTrue(is_string($db_res[0]["session_id"]));
		$this->assertTrue(strlen($db_res[0]["session_id"]) > 0);

		$this->assertTrue(is_string($db_res[0]["session_expires"]));
		$this->assertTrue(strlen($db_res[0]["session_expires"]) > 0);
		$this->assertTrue(is_numeric($db_res[0]["session_expires"]));

		$this->assertTrue(is_string($db_res[0]["session_data"]));
		$this->assertTrue(strlen($db_res[0]["session_data"]) > 0);

		$db_session_data = json_decode($db_res[0]["session_data"]);
		$this->assertTrue($db_session_data !== TRUE);

		$db_session_data = (array) $db_session_data;
		$this->assertEquals($db_session_data["datareadok1"], "ONCE");
		$this->assertEquals($db_session_data["datareadok2"], "TWICE");

		// Use the read() function ..
		$session_data = $lp_session_handler->read("someSessionID");

		// And verify we get the correct data
		$this->assertEquals((array) json_decode($session_data), $db_session_data);
		$this->assertEquals((array) json_decode($session_data), array("datareadok1" => "ONCE", "datareadok2" => "TWICE"));

		$lp_session_handler->destroy("someSessionID");

		$db_conn = NULL;
	}


	/** 
	 * @depends test_session_handler_read_ok
	 */

	public function test_session_handler_destroy_ok() {
		$lp_session_handler = new LPSessionHandler();
	
		$ret = $lp_session_handler->open("", "LP_SESSION");
		$this->assertTrue($ret);

		$lp_session_handler->write("someSessionID", json_encode(array("data1" => "one", "data2" => "two")));	

		// Check if it really worked
		$db_conn = lp_db_pdo_init();

		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions");
		$db_res = $db_stmt->fetchAll();


		// Do not assert the contents; that is done by the read/write tests
		$this->assertCount(1, $db_res);

		$lp_session_handler->destroy("someSessionID");

		// Check if it really worked
		$db_conn = lp_db_pdo_init();

		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions");
		$db_res = $db_stmt->fetchAll();

		$this->assertCount(0, $db_res);


		/*
		 * Write another record, then destroy one of the sessions,
		 * and check if only the newer one is in place.
		 */

		$ret = $lp_session_handler->open("", "LP_SESSION");
		$this->assertTrue($ret);

		$lp_session_handler->write("someSessionID", json_encode(array("data1" => "one", "data2" => "two")));	
		$lp_session_handler->write("someSessionIDTwo", json_encode(array("data1" => "one", "data2" => "two")));	

		// Check if it really worked
		$db_conn = lp_db_pdo_init();

		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions");
		$db_res = $db_stmt->fetchAll();


		// Do not assert the contents; that is done by the read/write tests
		$this->assertCount(2, $db_res);

		$lp_session_handler->destroy("someSessionID");

		// Check if it worked
		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions");
		$db_res = $db_stmt->fetchAll();

		$this->assertCount(1, $db_res);


		// Check if it worked
		$lp_session_handler->destroy("someSessionIDTwo");

		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions");
		$db_res = $db_stmt->fetchAll();

		$this->assertCount(0, $db_res);

		$db_conn = NULL;
	}


	/**
	 * @depends test_session_handler_read_ok
	 */

	public function test_session_handler_gc_ok() {
		// Ultra short lifetime for sessions

		ini_set("session.gc_maxlifetime", 2);

		$lp_session_handler = new LPSessionHandler();
	
		$ret = $lp_session_handler->open("", "LP_SESSION");
		$this->assertTrue($ret);

		// Create session
		$lp_session_handler->write("someSessionID", json_encode(array("data1" => "one", "data2" => "two")));	

		// Check if it really worked
		$db_conn = lp_db_pdo_init();
	
		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions");
		$db_res = $db_stmt->fetchAll();

		$this->assertCount(1, $db_res);


		// Now sleep so that the session will expire
		sleep(3);

		// Run garbage collection
		$lp_session_handler->gc(0);

		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions");
		$db_res = $db_stmt->fetchAll();

		$this->assertCount(0, $db_res);


		/*
		 * Repeat, but with two sessions.
		 * One will expire before the other.
		 * Soon after the first expires, we run garbage-collection,
		 * and the first should be picked up, but the second one not.
		 */

		ini_set("session.gc_maxlifetime", 10);

		$ret = $lp_session_handler->open("", "LP_SESSION");
		$this->assertTrue($ret);

		// Create sessions with different lifetimes
		$lp_session_handler->write("someSessionIDG", json_encode(array("data1" => "one", "data2" => "two")));
		sleep(3);

		$lp_session_handler->write("someSessionIDTwo", json_encode(array("data1" => "one", "data2" => "two")));	
		sleep(3);

		// Check if it really worked
	
		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions");
		$db_res = $db_stmt->fetchAll();

		$this->assertCount(2, $db_res);


		sleep(5);

		// Run garbage collection
		$lp_session_handler->gc(0);

		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions");
		$db_res = $db_stmt->fetchAll();

		$this->assertCount(1, $db_res);

		sleep(5);

		// Run garbage collection
		$lp_session_handler->gc(0);

		$db_stmt = $db_conn->query("SELECT session_id, session_expires, session_data FROM lp_sessions");
		$db_res = $db_stmt->fetchAll();

		$this->assertCount(0, $db_res);



		$db_conn = NULL;
	}

	/**
	 * @depends test_session_handler_gc_ok
	 */

	public function test_session_handler_close_ok() {
		$lp_session_handler = new LPSessionHandler();

		$ret = $lp_session_handler->open("", "LP_SESSION");
		$this->assertTrue($ret);

		$ret = $lp_session_handler->close();
		$this->assertTrue($ret);
	}
} 

