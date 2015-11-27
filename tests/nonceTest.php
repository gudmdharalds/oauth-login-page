<?php

require_once(__DIR__ . "/../nonce.php");

class Nonce extends PHPUnit_Framework_TestCase {
	public function __construct() {
		global $lp_config;

		$lp_config["nonce_hashing_function"] = "sha256";

		$this->static_secret = (string) rand() . "RanDomNssNotReally";
		$this->session_secret = (string) rand() . "SomeOtherRAndomNess";
	}

	public function __destruct() {
		global $lp_config;

		unset($this->static_secret);
		unset($this->session_secret);

		unset($lp_config["nonce_hashing_function"]);
	}

 	public function test_generate_static_secret_empty() {
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
		$random_bytes = openssl_random_pseudo_bytes(60, $openssl_crypto_strong);

		$this->assertTrue($random_bytes !== FALSE);
		$this->assertTrue($openssl_crypto_strong !== FALSE);
	} 

 
	public function test_generate_hash_function_invalid() {
    		global $lp_config;

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
		$nonce = lp_nonce_generate(
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
		$static_secret = "someULTRAmegasecretstring1";
		$session_secret = "someULTRAmegaSECRETstring2";

		$nonce1 = lp_nonce_generate(
			$static_secret,
			$session_secret,
			5
		);

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