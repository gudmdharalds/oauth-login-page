<?php

require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/shared.php");

class NonceTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		global $lp_config;

		$lp_config = __lp__unittesting_lp_config_fake();

		$lp_config["nonce_hashing_function"] = "sha256";
		$lp_config["time_func"] = 'time';
		$lp_config["openssl_random_pseudo_bytes_func"] = 'openssl_random_pseudo_bytes';

		$this->static_secret = (string) rand() . "RanDomNssNotReally___";
		$this->session_secret = (string) rand() . "SomeOtherRAndomNess__";

		// Save snapshot
		__lp__unittesting_superglobals_snapshot(TRUE);
	}


	public function tearDown() {
		global $lp_config;

		unset($this->static_secret);
		unset($this->session_secret);

		unset($lp_config);
        	
		// Put snapshot in place
		__lp__unittesting_superglobals_snapshot(FALSE);        
	}


 	public function test_generate_static_secret_empty() {
		PHPUnit_Framework_Error_Notice::$enabled = FALSE;

		$nonce = @lp_nonce_generate(
			"",
			$this->session_secret,
			100
		);

		$this->assertFalse($nonce);

		$this->assertEquals(
			error_get_last()["message"],
			"Missing valid session or static secret"
		);
	}

   
	public function test_generate_static_secret_too_short() {
		PHPUnit_Framework_Error_Notice::$enabled = FALSE;

		$nonce = @lp_nonce_generate(
			"veryshort",
			$this->session_secret,
			100
		);

		$this->assertFalse($nonce);
	
		$this->assertEquals(
			error_get_last()["message"],
			"Missing valid session or static secret"
		);
	}


	public function test_generate_session_secret_empty() {
		PHPUnit_Framework_Error_Notice::$enabled = FALSE;

 		$nonce = @lp_nonce_generate(
			$this->static_secret,
			"",
			100
		);

		$this->assertFalse($nonce);

		$this->assertEquals(
			error_get_last()["message"],
			"Missing valid session or static secret"
		);
	}

 
	public function test_generate_session_secret_too_short() {
		PHPUnit_Framework_Error_Notice::$enabled = FALSE;

		$nonce = @lp_nonce_generate(
			$this->static_secret,
			"tooshort",
			100
		);
		
		$this->assertFalse($nonce);

		$this->assertEquals(
			error_get_last()["message"],
			"Missing valid session or static secret"
		);
	}  
 
	public function test_generate_timeout_invalid() {
		PHPUnit_Framework_Error_Notice::$enabled = FALSE;

		$nonce = @lp_nonce_generate(
			$this->static_secret,
			$this->session_secret,
			-50
		);

              
		$this->assertFalse($nonce);
               
		$this->assertEquals(
			error_get_last()["message"],
			"Invalid nonce timeout specified"
		);
	}
  
	public function test_generate_random_pseudo_bytes_ok() {
		$lp_config["openssl_random_pseudo_bytes_func"] = "openssl_random_pseudo_bytes";

		$random_bytes = lp_openssl_random_pseudo_bytes(60, $openssl_crypto_strong);

		$this->assertTrue($random_bytes !== FALSE);
		$this->assertTrue($openssl_crypto_strong !== FALSE);
	} 

 
	public function test_generate_hash_function_invalid() {
    		global $lp_config;

		PHPUnit_Framework_Error_Notice::$enabled = FALSE;

		$lp_config["nonce_hashing_function"] = "smurningur100";
	
		$nonce = @lp_nonce_generate(
			$this->static_secret,
			$this->session_secret,
			100
		);

		$this->assertFalse($nonce);

		$this->assertEquals(
			error_get_last()["message"],
			"Hashing failed"
		);
	}

    
	public function test_generate_ok() {
		PHPUnit_Framework_Error_Notice::$enabled = FALSE;

		$nonce = @lp_nonce_generate(
			$this->static_secret,
			$this->session_secret,
			100
		);


		$this->assertThat(
			$nonce,
          
			$this->logicalNot(
				$this->equalTo(FALSE)
			)
		);

		$nonce_arr = explode(",", $nonce);

		/*
		 * Test if nonce looks right 
		 * 
		 * i.e., has three fields separated by ","
		 * and that the first is a string (salt), second is a 
		 * numeric (timestamp), and the third is a string (hash).
		 */

		$this->assertCount(3, $nonce_arr);

		$this->assertTrue(is_string($nonce_arr[0]));
		$this->assertTrue(is_numeric($nonce_arr[1]));
		$this->assertTrue(is_string($nonce_arr[2]));

		// Check if the nonce checks out good
		$this->assertTrue(
			lp_nonce_check(
				$this->static_secret,
				$this->session_secret,
				$nonce
			)
		);
	}


	public function test_check_nonce_not_string() {
		$this->assertFalse(
			@lp_nonce_check(
					$this->static_secret,
					$this->session_secret,
					1993
			)
		);

		$this->assertEquals(
			error_get_last()["message"],
			"Invalid nonce - not string"
		);
	}


	public function test_check_nonce_invalid_length() {
		$this->assertFalse(
			@lp_nonce_check(
					$this->static_secret,
					$this->session_secret,
					"a,b"
			)
		);

		$this->assertEquals(
			error_get_last()["message"],
			"Invalid nonce - illegal size"
		);


		$this->assertFalse(
			@lp_nonce_check(
					$this->static_secret,
					$this->session_secret,
					"a,b,c,d"
			)
		);

		$this->assertEquals(
			error_get_last()["message"],
			"Invalid nonce - illegal size"
		);
	}


	public function test_check_nonce_expired() {
		$this->assertFalse(
			@lp_nonce_check(
					"somemegasecretstring1",
					"othermegasecretstring2",
					"CaynL1OJvzksHzfFFi0Sny4JaN8NDEQLMa0OIcYEJdR8eR6//k0cHuYWEoGJQYQs/WKHIPKWgd3mli4r,1448627505,4037e5b7cca1fa455fac887bf4ddbdfa844b4ba31b6749152b09822d383ac7af" // All valid, except that nonce-string has expired
			)
		);

		$this->assertEquals(
			error_get_last()["message"],
			"Nonce has expired"
		);
	}

	public function test_check_nonce_invalid_salt_changed() {
		$this->assertFalse(
			@lp_nonce_check(
					"somemegasecretstring1",
					"othermegasecretstring2",
					"DAynL1OJvzksHzfFFi0Sny4JaN8NDEQLMa0OIcYEJdR8eR6//k0cHuYWEoGJQYQs/WKHIPKWgd3mli4r,1448627505,4037e5b7cca1fa455fac887bf4ddbdfa844b4ba31b6749152b09822d383ac7af" // First two chars of salt changed
			)
		);

		$this->assertEquals(
			error_get_last()["message"],
			"Nonce invalid - hash does not match"
		);
	}


	public function test_check_nonce_invalid_hash_changed() {
		$this->assertFalse(
			@lp_nonce_check(
					"somemegasecretstring1",
					"othermegasecretstring2",
					"CaynL1OJvzksHzfFFi0Sny4JaN8NDEQLMa0OIcYEJdR8eR6//k0cHuYWEoGJQYQs/WKHIPKWgd3mli4r,1448627505,4037e5b7cca1fa455fac887bf4ddbdfa844b4ba31b6749152b09822d383ac77c" // Last two chars changed in hash-string
			)
		);

		$this->assertEquals(
			error_get_last()["message"],
			"Nonce invalid - hash does not match"
		);
	}

	public function test_check_nonce_ok() {
		#PHPUnit_Framework_Error_Notice::$enabled = FALSE;

		$static_secret = "someULTRAmegasecretstring1";
		$session_secret = "someULTRAmegaSECRETstring2";

		$nonce1 = lp_nonce_generate(
			$static_secret,
			$session_secret,
			5 // Expiry time in 5 sec...
		);

		$this->assertTrue($nonce1 !== FALSE);

		$this->assertTrue(
			@lp_nonce_check(
				$static_secret,
				$session_secret,
				$nonce1
			)
		);

		// Wait until it expires, then check again...	
		sleep(6);
	
		$this->assertFalse(
			@lp_nonce_check(
				$static_secret,
				$session_secret,
				$nonce1
			)	
		);
	}

}

?>
