<?php 


/*
 *
 * A tiny Nonce generator with variable time-outs.
 * 
 * No database required.
 * Each Nonce has its own Salt.
 * 
 */

/*
 * Generate a Nonce. 
 * 
 *
 */

	
function lp_nonce_generate($static_secret, $session_secret, $timeout = 180) {

	/*
	 * Check if secrets provided fulfill the minimum
	 * requirements: Being strings, and 20 characters of length.
	 */

	if (
		(is_string($static_secret) == FALSE) || 
		(strlen($static_secret) < 20) ||
		(is_string($session_secret) == FALSE) || 
		(strlen($session_secret) < 20) 
	) {
		lp_fatal_error("Missing valid secret");
	}


	/*
	 * Make sure that the timeout is non-zero.
	 */

	if ($timeout <= 0) {
		lp_fatal_error("Invalid nonce timeout specified");
	}


	/*
	 * Try to get some random bytes for salt, and if it fails,
	 * abort everything.
	 */

	$random_bytes = openssl_random_pseudo_bytes(60, $openssl_crypto_strong);

	if ($openssl_crypto_strong === FALSE) {
		lp_fatal_error("Could not get random bytes");
	}

	/*
	 * The random string from the above function might contain
	 * 'odd'-characters -- base64 for sanity.
	 */
		  
	$salt = base64_encode($random_bytes);

	if ($salt === FALSE) {
		lp_fatal_error("Unable to encode random bytes");
	}


	// Create a timestamp of when the nonce-string should expire
	$nonce_expiry_timestamp = time() + $timeout;


	/*
	 * Actually generate the nonce.
	 */

	       
	return $salt . "," . $nonce_expiry_timestamp . "," .  
		lp_nonce_hash_gen(
			$salt, 
			$static_secret, 
                        $session_secret, 
			$nonce_expiry_timestamp
		);
}
	
	
/*
 * 
 */

function lp_nonce_check($static_secret, $session_secret, $nonce) {
	// Check if $nonce is a string ... 
	if (is_string($nonce) == FALSE) {
		return FALSE;
	}


	/*
	 * Split nonce into an array at commas (",").
 	 * 
	 * The resulting array should contain exactly three
	 * items -- if it does not, the nonce is malformed and
	 * an error is returned.
	 */

	$nonce_arr = explode(',', $nonce);

	if (count($nonce_arr) !== 3) {
		return FALSE;
	}


	/*
	 * Now harvest what should be 'salt', the timestamp when
	 * the nonce becomes expired, and a hash made of the salt, 
	 * secrets and the expiry timestamp.
	 */

	$salt = $nonce_arr[0];
	$nonce_expiry_timestamp = intval($nonce_arr[1]);
	$hash = $nonce_arr[2];



	/*
	 * Now calculate, based on the salt harvested,
	 * the secrets, and expiry time, what the resulting
	 * hash should be of all of these.
	 * 
	 * If the resulting hash does not match what we were
	 * given, return an error.
	 */

	$hash_back = lp_nonce_hash_gen($salt, $static_secret, 
			$session_secret, $nonce_expiry_timestamp);

	if ($hash_back !== $hash) {
		return FALSE;
	}


	/*
	 * The nonce should not have expired. If it has,
	 * return an error.
	 */

	if (time() > $nonce_expiry_timestamp) {
		return FALSE;
	}

	// All checks fine, return TRUE.
	return TRUE;
}

function lp_nonce_hash_gen($salt, $static_secret, $session_secret, $nonce_expiry_timestamp) {
	global $lp_config;

	return hash($lp_config["nonce_hashing_function"], 
		(
			$salt . 
			$static_secret . 
			'-' . 
			$session_secret . 
			(string) $nonce_expiry_timestamp
		)
	);
}
