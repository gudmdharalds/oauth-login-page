<?php

require_once(__DIR__ . "/../nonce.php");

class Nonce extends PHPUnit_Framework_TestCase {
	

	public function test_generate_static_secret_empty() {
    
	}
    
	public function test_generate_session_secret_empty() {
    
	}
    
	public function test_generate_timeout_invalid() {
    
	}
    
	public function test_generate_hash_function_invalid() {
    		global $lp_config;

		$lp_config["nonce_hashing_function"] = "smurningur100";
	
		$static_secret = (string) rand() . "RanDomNssNotReally";
		$session_secret = (string) rand() . "SomeOtherRAndomNess";

		$nonce = lp_nonce_generate(
			$static_secret,
			$session_secret,
			100
		);
	

		$self->assertFalse($nonce);
	
	}
    
	public function test_generate_ok() {
		global $lp_config;
	
		$lp_config["nonce_hashing_function"] = "sha256";
	
		$static_secret = (string) rand() . "RanDomNssNotReally";
		$session_secret = (string) rand() . "SomeOtherRAndomNess";

			$nonce = lp_nonce_generate(
			$static_secret,
			$session_secret,
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
				$static_secret,
				$session_secret,
				$nonce
			)
		);
	}

}

?>
