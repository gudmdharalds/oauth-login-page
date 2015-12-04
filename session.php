<?php

/*
 * LP_GENERATE_SESSION_TOKEN:
 *
 * Generate session tokens. Will first retrieve random
 * bytes of data via OpenSSL, which then is transformed into
 * a string using SHA256. This string is then returned.
 *
 * The function might return an error, if the OpenSSL function
 * is unable to generate cryptographically string random bytes.
 *
 * Returns:
 *      Random string on success, FALSE on error.
 */

function lp_generate_session_secret() {
	global $lp_config;

	$crypto_strong = FALSE;

	// Get 20 * 1024 bytes of random data
	$randomstring = lp_openssl_random_pseudo_bytes(20 * 1024, $crypto_strong);

	if ($crypto_strong === TRUE) {
		return hash($lp_config["session_secret_function"], $randomstring, FALSE);
	}

	else {
		return FALSE;
	}
}

function lp_session_init() {
	global $lp_config;
	global $_SERVER;
	global $_SESSION;


	/*
	 * Instruct PHP not to put the session ID in any URLs,
	 * and to accept session IDs only from cookies.
	 * 
	 * This increases security, making it harder to hijack sessions.
	 */

	lp_ini_set("session.use_trans_sid", 0);
	lp_ini_set("session.use_only_cookies", 1);



	/*
	 * Instruct PHP to accept only session IDs that
	 * are recognized by this system - and nothing else.
	 * But only on versions PHP that support it ...
	 */

	if (version_compare(PHP_VERSION, '5.5.2', '>=') === TRUE) {
		lp_ini_set('session.use_strict_mode', 1);
	}

	/*
	 * Accept cookies only via HTTP - and make sure
	 * that JavaScript cannot access them.
	 */

	lp_ini_set('session.cookie_httponly', 1);


	/* 
	 * Instruct PHP to use the configured hashing function,
	 * and to generate the session ID from the specified entropy
	 * length. This will increase security, making IDs harder to
	 * guess.
	 *
	 * This is per the recommendations put forward here:
	 * http://stackoverflow.com/questions/5081025/php-session-fixation-hijacking
	 * 
	 */
	
	lp_ini_set("session.hash_function", $lp_config["session_hashing_function"]);
	lp_ini_set("session.entropy_length", $lp_config["session_entropy_length"]);


	// Set session name
	session_name('LP_SESSION');


	/* 
	 * Set our own session handler
	 */

	$lp_session_handler = new LPSessionHandler();

	if (session_set_save_handler($lp_session_handler, TRUE) !== TRUE) {
		lp_fatal_error("Unable to start session since could not set session handler");

	}

	/*
	 * All prepared now.
	 * Actually start a session.
	 */

	
	if ((call_user_func($lp_config["session_start_func"])) !== TRUE) {
		lp_fatal_error("Could not start session");
	}


	/*
	 * Check if user has any session token attached to his session.
	 * If not, generate one and attach. See lp_login_form()
	 * for a detailed description of why this is necessary.
	 */

	if (isset($_SESSION{"lp_nonce_session_secret"}) === FALSE) {
		$lp_nonce_session_secret = lp_generate_session_secret();
        
		if ($lp_nonce_session_secret === FALSE) {
			lp_fatal_error("Cannot continue configuring session; the system is not correctly configured.");
		}
        
		$_SESSION{"lp_nonce_session_secret"} = $lp_nonce_session_secret;

		session_write_close();
	}


	/*
	 * Check a user-agent is attached
	 * to the session started, and if not,
	 * attach reported user-agent type to it.
	 */

	if (isset($_SESSION['lp_user_agent']) === FALSE) {
		$_SESSION['lp_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
	}

	else {
		/*
		 * We have user-agent attached to our session; now check
		 * if the reported user-agent in the request made matches
	 	 * the one saved earlier in the session.
		 *
		 * If they do not match, this might indicate a potential
		 * theft of the session, so we destroy the session, the 
		 * cookie and report an error. 
		 */

		if ($_SESSION['lp_user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
 			$session_cookie_params = session_get_cookie_params();
   
			// Destroy the cookie previously sent to the browser. 
			setcookie(
				session_name(), 
				'', 
				lp_time() - 42000,
				$session_cookie_params["path"], 
				$session_cookie_params["domain"],
				$session_cookie_params["secure"], 
				$session_cookie_params["httponly"]
			);

			/*
			 * Then destroy the session -
			 * note that if we cannot destroy the 
			 * session, we cannot do much about it.
			 * We are failing anyway.
			 */

			session_destroy();

			// And report a fatal error
			lp_fatal_error("Could not start a session");
		}
	}
}

class LPSessionHandler implements SessionHandlerInterface {

	/*
 	 * This class implements a SessionHandlerInterface.
	 * Roughly, this involves handling when a new session is opened,
	 * closed, created, and destroyed, as well as handling
	 * cleaning up unused sessions.
	 */

	// session-lifetime
	var $session_lifetime;
    
	// mysql-handle
	var $db_conn;


	function open($savePath, $sessName) {
		// get session-lifetime
		$this->session_lifetime = ini_get("session.gc_maxlifetime");

		/*
		 * Try to open database-connection - 
		 * if it fails, return FALSE.
		 */

		$this->db_conn = lp_db_pdo_init();

		// FIXME: Do something with this?
#		$this->db_conn->setAttribute(PDO::ATTR_AUTOCOMMIT, TRUE);
      
		if ($this->db_conn === FALSE) {
			return FALSE;
		}
       
		return TRUE;
	}

 
	function close() {
		/* 
		 * Start by doing a clean up of sessions 
		 * note that the first parameter is ignored by
		 * this function, so we just put in NULL.
		 */

		$this->gc(NULL);

        
		/*
		 * Destroy the database handle -- 
		 * that should signal PHP to close it.
		 */

		$this->db_conn = NULL;

		return TRUE;
	}

    
	function read($session_id) {
		/*
		 * Fetch session data from DB,
		 * by session ID, but only if the session
		 * still has not expired.
 		 */

		$db_stmt = $this->db_conn->prepare(
				"SELECT session_data AS session_data FROM lp_sessions " . 
				"WHERE session_id = :session_id " .
				"AND session_expires > :session_expires");

		// Any error? Return empty string.
		if ($db_stmt === FALSE) {
			return "";
		}


		$db_ret = $db_stmt->execute(array(
			":session_id"		=> $session_id,
			":session_expires"	=> lp_time(),
		));

		// If error, return empty string.
		if ($db_ret === FALSE) {
			return "";
		}


		$db_row = $db_stmt->fetch(PDO::FETCH_ASSOC);
        
		/*
		 * Return data or an empty string at failure
		 */

		if ($db_row !== FALSE) {
			return $db_row['session_data'];
		}

		else {
			return "";
		}
	}

    
	function write($session_id, $session_data) {
		// New session-expire-time
		$session_expiry_new = lp_time() + $this->session_lifetime;
       
		/*
		 * Try to find session with the specified ID in the database.
		 *
		 * If we encounter any problem with DB, return FALSE.
		 */

		$db_stmt = $this->db_conn->prepare(
			"SELECT session_id, session_data, session_expires " .
			"FROM lp_sessions " . 
			"WHERE session_id = :session_id"
		);

		if ($db_stmt === FALSE) {
			return FALSE;
		}


		/*
		 * Now execute our query -- return FALSE on DB error. 
		 */

		$db_ret = $db_stmt->execute(array(
			":session_id"		=> $session_id
		));

		if ($db_ret === FALSE) {
			return FALSE;
		}

        
		// If the requested session was found ...
		if ($db_stmt->rowCount() > 0) {

			/*
			 * Try to update session data in DB.
			 * If that fails, return FALSE, but if it
			 * succeeds, return TRUE.
			 */
           		
			$db_stmt = $this->db_conn->prepare( 
				"UPDATE lp_sessions " .
				"SET session_expires = :session_expires, " .
				"session_data = :session_data " .
				"WHERE session_id = :session_id"
			);

			if ($db_stmt === FALSE) {
				return FALSE;
			}

			/*
			 * Execute DB query - return FALSE upon error.
			 */

			$db_ret = $db_stmt->execute(array(
				":session_id"			=> $session_id,
				":session_expires"		=> $session_expiry_new,
				":session_data"			=> $session_data,
			));

			if ($db_ret === FALSE) {
				return FALSE;
			}


			/*
			 * If we were able to update session data in DB, 
			 * return TRUE - else FALSE.
			 */
			
			if ($db_stmt->rowCount() > 0) {
				return TRUE;
			}

			else {
				return FALSE;
			}
		}

        
		/*
		 * But if no session-data was found, create a new 
		 * record in the database of this session
		 */

		else {
			/* 
			 * Try to insert into the DB.
			 * Return FALSE up on DB error.
			 */
	
			$db_stmt = $this->db_conn->prepare(
				"INSERT INTO lp_sessions (
		                         session_id,
		                         session_expires,
                		         session_data
				) VALUES (
					:session_id,
					:session_expires,
					:session_data
				)");

			if ($db_stmt === FALSE) {
				return FALSE;
			}


			/*
			 * Execute DB query. Return FALSE
			 * up on DB error.
			 */

			$db_ret = $db_stmt->execute(array(
				":session_id"			=> $session_id,
				":session_expires"		=> $session_expiry_new,
				":session_data"			=> $session_data,
			));

			if ($db_ret === FALSE) {
				return FALSE;
			}
            
			// if row was created, return true
			if ($db_stmt->rowCount() > 0) {
				return TRUE;
			}

			else {
				return FALSE;
			}
		}

		// Should never happen.
		return FALSE; 
	}

    
	function destroy($session_id) {
		/*
		 * Delete session-data.
		 * Return FALSE up on error.
		 */

		$db_stmt = $this->db_conn->prepare(
			"DELETE FROM lp_sessions " .
			"WHERE session_id = :session_id"
		);

		if ($db_stmt === FALSE) {
			return FALSE;
		}


		/*
		 * Execute -- return FALSE up on error.
		 */

		$db_ret = $db_stmt->execute(array(
			":session_id"		=> $session_id
		));

		if ($db_ret === FALSE) {
			return FALSE;
		}


		/*
		 * If we threw away session data from DB,
		 * return TRUE, but else return FALSE.
		 */
        
		if ($db_stmt->rowCount() > 0) {
			return TRUE;
		}

		else {
			return FALSE;
		}
	} 
   
 
	function gc($maxlifetime) {

		/*
		 * Delete old sessions from DB.
		 */

		$db_stmt = $this->db_conn->prepare("DELETE FROM lp_sessions WHERE session_expires < :session_expires");

		if ($db_stmt === FALSE) {
			return FALSE;
		}

		/*
		 * Execute -- return FALSE up on error.
		 */

		$db_res = $db_stmt->execute(array(
			":session_expires" => lp_time()
		));

		if ($db_res === FALSE) {
			return FALSE;
		}
        
		return TRUE;
	} 
}



