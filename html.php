<?php

/*
 * LP_TPL_OUTPUT:
 *
 * Output template to user-agent.
 *
 * Outputs template specified by $template_file_path,
 * and replace placeholders in the template with 
 * the values specified by $replacement_strings_arr.
 * 
 */

function lp_tpl_output($replacement_strings_arr, $template_file_path) {
	/*
	 * Try to open the template-file.
 	 * If that fails, we cannot continue.
 	 */

	$template_file_content = file_get_contents($template_file_path);

	if ($template_file_content === FALSE) {
		$error_msg = error_get_last();

		lp_fatal_error("Could not read file \"" . $template_file_path . "\": " . $error_msg["message"]);
	}

	/*
	 * Replace placeholders with values
	 */

	echo str_replace(
		array_keys($replacement_strings_arr), 
		array_values($replacement_strings_arr), 
		$template_file_content
	);
}


/*
 * LP_HTML_HEADER:
 *
 * Output HTML header as specified in
 * tpl/header.tpl.php. Will provide values to replace 
 * placeholders with.
 */

function lp_html_header() {
	global $lp_config;
	static $func_called = FALSE;

	// If called before, don't output again.
	// This function might be called twice in some edge-cases.
	if ($func_called === TRUE) {
		return;
	}

	$tpl_replacements = array(
		"%image_icon%"		=> $lp_config["image_icon"],
		"%page_title_prefix%"	=> $lp_config["page_title_prefix"],
		"%page_author%"		=> (isset($lp_config["page_author"]) ? $lp_config["page_author"] : ""),
		"%page_copyright%"	=> (isset($lp_config["page_copyright"]) ? $lp_config["page_copyright"] : ""),
		"%css_file%"		=> $lp_config["css_file"],
		"%lp_box1%"		=> (isset($lp_config["lp_box1"]) ? $lp_config["lp_box1"] : ""),
		"%lp_box2%"		=> (isset($lp_config["lp_box2"]) ? $lp_config["lp_box2"] : ""),
		"%lp_box3%"		=> (isset($lp_config["lp_box3"]) ? $lp_config["lp_box3"] : ""),
	);

        lp_tpl_output($tpl_replacements, "tpl/header.tpl.php");

	$func_called = TRUE;
}


/*
 * LP_HTML_FOOTER:
 *
 * Output HTML footer to user-agent.
 */

function lp_html_footer() {
	lp_tpl_output(array(), "tpl/footer.tpl.php");
}


/*
 * LP_LOGIN_FORM: 
 *
 * Output login-form to user-agent.
 * Will first create a nonce-string, and then
 * output the login-form.
 */

function lp_login_form($error_msg = "") {
	global $lp_config;

	/*
	 * Use previously generated random and unpredictable 
	 * session secret that is saved in our $_SESSION variable,
	 * (which is not visible to browsers nor end-users),
 	 * to generate nonce strings (along with other data).
	 * 
	 * By doing this, we guarantee that malicious 
	 * agents cannot simply enter this page, harvest
	 * some nonce string, and then use it in
	 * their fake-login forms they provide to innocent users (which
	 * they then submit): This is because the nonce string 
	 * they fetched is attached to the session initiated for 
	 * the malicious agent - and not to the innocent user's 
	 * session. Hence nonce token validation will fail 
	 * when attempted this way. The malicious attacker has to
	 * steal the user's session cookie as well - hard to accomplish.
	 *
	 * Along with the session secret, we also use a nonce static secret 
	 * which is only known to the current site. The static secret and the
	 * session secret are provided to the nonce generator, and function as a 
	 * single nonce secret. This combination should be unique for each session 
	 * - and is kept secret to all user-agents accessing the site.
	 *
	 * This is implemented as per recommendation found here:
	 * https://www.owasp.org/index.php/CSRF_Prevention_Cheat_Sheet#General_Recommendation:_Synchronizer_Token_Pattern
	 */

	$nonce = lp_nonce_generate(
		$lp_config["nonce_static_secret_key"],			# Static secret used in nonce generation
		$_SESSION{"lp_nonce_session_secret"}, 			# Session scecret to generate nonce strings
		260							# Expires in 260 seconds
	);

	// If we encounter any error, report fatal error
	if ($nonce === FALSE) {
		$error_last = error_get_last();

		lp_fatal_error($error_last["message"]);
	}

	// Get scopes info
	$scopes_info = lp_scope_info_get();

	/*
	 * Split requested scopes to create array; 
	 * then loop through, and get description from
	 * the scopes-info fetched from OAuth server.
	 * Create HTML code from that.
	 */

	$req_scope_arr = explode(" ", $_REQUEST{"scope"});
	$tpl_scopes_list = "";

	foreach ($req_scope_arr as $req_scope_arr_item) {
		/*
		 * Make sure we have information about requested
		 * scope -- and if not, fail.
		 */
		
		if (isset($scopes_info[$req_scope_arr_item]) === FALSE) {
			lp_fatal_error("Could not get information about scope");
		}

		// Construct some nice HTML around the scope information...
		$tpl_scopes_list .= 
			"<li>" . 
			htmlentities($scopes_info[$req_scope_arr_item])
			. "</li>";
	}


	/* 
	 * If we actually didn't get any information about 
	 * any scope, fail.
	 */

	if (empty($tpl_scopes_list) === TRUE) {
		lp_fatal_error("Could not get information about scope");
	}


	// FIXME: Do this in index.php ?
	$client_uri = urldecode($_REQUEST{"redirect_uri"});
	$client_uri_arr = parse_url($client_uri);

	if (
		($client_uri_arr === FALSE) || 
		(isset($client_uri_arr["host"]) === FALSE)
	) {
		lp_fatal_error("Redirect URI is illegal");
	}


	$tpl_replacements = array(
		"%h1_caption%"		=> $lp_config["login_form_heading"],	
		"%image_page%"		=> $lp_config["image_page"],
		"%client_uri%"		=> $client_uri_arr["host"],
		"%scope_list%"		=> $tpl_scopes_list,
		"%response_type%"	=> htmlentities($_REQUEST{"response_type"}),
		"%client_id%"		=> htmlentities($_REQUEST{"client_id"}), 
		"%redirect_uri%"	=> htmlentities($_REQUEST{"redirect_uri"}),
		"%scope%"		=> htmlentities($_REQUEST{"scope"}),
		"%state%"		=> htmlentities($_REQUEST{"state"}),	
		"%nonce%"		=> htmlentities($nonce),
		"%error_msg%"		=> "",
	);

	// If any error-message was specified ...
	if (empty($error_msg) === FALSE) {
		$tpl_replacements["%error_msg%"] = $lp_config["login_form_error_prefix"] . 
			" " . $error_msg . " " . 
			$lp_config["login_form_error_suffix"];
	}


	lp_html_header();

	// FIXME: Send scope information...

	lp_tpl_output($tpl_replacements, "tpl/login-form.tpl.php");
		
	lp_html_footer();
}


/*
 * LP_FATAL_ERROR:
 *
 * Output fatal-error message to user, specified in $msg.
 *
 */

function lp_fatal_error($msg) {
	lp_html_header();

	$tpl_replacements = array(
		"%error_msg%"		=> $msg
	);

	lp_tpl_output($tpl_replacements, "tpl/error.tpl.php");
	
	lp_html_footer();

	exit(0);
}

