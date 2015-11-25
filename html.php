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
	 * session token that is saved in our $_SESSION variable,
	 * (which is not visible to browsers nor end-users),
 	 * to generae nonce strings.
	 * 
	 * By doing this, we guarantee that malicious 
	 * agents cannot simply enter this page, harvest
	 * some nonce string, and then use it in
	 * their fake-login forms they provide to users (which
	 * users then submit): This is because the nonce string 
	 * they fetched is attached to the  session initiated for 
	 * the malicious agents - and not to the innocent user's 
	 * session. Hence the nonce token validation will fail 
	 * when attempted this way.
	 *
	 * Along with the session token, we also use a nonce secret key
	 * which is only knonw to the current site. The secret key and the
	 * token are concaternated together, and are provided as a 
	 * nonce secret for the NonceUtil class. This combination should
	 * be unique for all sessions - and is secret to all user-agents
	 * accessing the site.
	 *
	 * This is implemented as per recommendation found here:
	 * https://www.owasp.org/index.php/CSRF_Prevention_Cheat_Sheet#General_Recommendation:_Synchronizer_Token_Pattern
	 */

	$nonce = NonceUtil::generate(
		$lp_config["nonce_secret_key"] . '-' . $_SESSION{"lp_session_token"}, 		# Secret to generate nonce strings
		260										# Expires in 260 seconds
	);

	lp_html_header();

	$tpl_replacements = array(
		"%h1_caption%"		=> "Please log in",	
		"%error_msg%"		=> $error_msg,
		"%image_page%"		=> $lp_config["image_page"],
		"%redirect_uri%"	=> $_REQUEST{"redirect_uri"},
		"%nonce%"		=> $nonce,
	);

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

