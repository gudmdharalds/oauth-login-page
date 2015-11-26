<?php

/*
 * Copy this file as config.php and 
 * make your changes there.
 */

function lp_config() {
	$lp_config = array(
		// Author and copyright information.
		"page_copyright"			=> "" // e.g. "(C) Your Name 2015",
		"page_author"				=> "" // e.g. "Your Name",

		// Something to convay who runs this page
		"page_title_prefix"			=> "" // e.g. "Your SiteName",

		// Login-form information blurbs
		"login_form_heading"			=> "Please log in",
		"login_form_error_prefix"		=> "Woops. Cannot log you on: ",
		"login_form_error_suffix"		=> " ",


		// Content areas in the <body> of the page
		// Use HTML, if you like.
		"lp_box1"				=> "", // e.g. "<span>Really Cool HTML</span>"
		"lp_box2"				=> "",
		"lp_box3"				=> "",


		// These should be absolute paths or URIs to images
		"image_icon"				=> "" // e.g. "/static/login.png",
		"image_page"				=> "" // e.g. "http://X.Y.W.Z/login.png" 
	
		// This should be absolute path or URI to CSS
		"css_file"				=> "" // e.g. "/static/oauth-login-page.css", 


		/*
		 * Clients provide us with URIs which specify
		 * where to redirect successful user-agents.
		 * This should be a list of valid URIs.
		 */

		"valid_redirect_uris" 			=> array(
			// e.g. "http://X.Y.W.Z/redirect.php",
		),

		/*
		 * DB configuration. See http://php.net/manual/en/pdo.construct.php for details.
		 */

		"db_driver"				=> "" // e.g. "mysql",
		"db_name"				=> "" // e.g. "oauth_login_page",
		"db_host"				=> "" // e.g. "172.17.0.10",
		"db_user"				=> "" // e.g. oauth_login_page",
		"db_pass"				=> "" // i.e. some long random string,

	
		// OAuth 2.0 server - full URI to access-token endpoint
		"oauth2_server_access_token_uri"	=> "" // e.g. "http://172.16.254.6/access_token",	
		"oauth2_client_id"			=> "" // e.g. "testclient",
		"oauth2_client_secret"			=> "" // e.g. "secret",

	
		/*
		 * Secret so that we can perform NONCE checks.
		 * To generate, you can use a shell command
		 * such as this:
		 *
		 * $ head -c 10000000 /dev/urandom | sha256sum
		 *
		 * and then harvest the long string outputted to use
		 * as a secret.
		 *
		 * NOTE: Do not use MD5 nor SHA1 - they are not secure enough.
		 * SHA256 is highly recommended.
		 */

		"nonce_static_secret_key"			=> 
			"" // i.e. a random string 


		/*
		 * Set what hashing function should be used for session IDs -
		 * defaults to SHA256.
		 *
		 * Also, how many bytes should be read to generate the session identifier.
		 *
		 * Here, one can also set what hashing function should be used to
		 * generate session tokens - defaults to SHA256.
		 *
		 * Note: Do not attempt to use MD4, MD5 or SHA1 -- they are not secure anymore
		 * and should not implemented in new systems.
		 */
		
		"session_hashing_function"		=> "sha256",
		"session_entropy_length"		=> "768",
		"session_secret_function"		=> "sha256",
		"nonce_hashing_function"		=> "sha256",
	);

	return $lp_config;
}


