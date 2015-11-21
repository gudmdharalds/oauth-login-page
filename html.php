<?php


function lp_html_header() {
	global $lp_config;

	// FIXME: tpl/html-header.tpl.php
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head> 
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<?php if (isset($lp_config["image_icon"])): ?>
        <link rel="icon" type="image/png" href="<?php echo $lp_config["image_icon"]; ?>" />
	<?php endif; ?>

        <title><?php if (isset($lp_config["page_title_prefix"])): echo $lp_config["page_title_prefix"]; endif; ?> - Login</title>
     
        <!-- START Meta Information -->
 	<?php if (isset($lp_config["page_author"])): ?>
       <meta name="author" content="<?php echo $lp_config["page_author"]; ?>" />
	<?php endif; ?>

	<?php if (isset($lp_config["page_copyright"])): ?>
        <meta name="copyright" content="<?php echo $lp_config["page_copyright"]; ?>" />
	<?php endif; ?>

        <meta name="robots" content="noindex,nofollow,noarchive,nosnippet,noimageindex" />
        <!-- END Meta Information -->
    
        <!-- START Style Sheets -->
	<?php if (isset($lp_config["css_file"])): ?>
        <link rel="stylesheet" type="text/css" media="all" href="<?php echo $lp_config["css_file"]; ?>" /> 
	<?php endif; ?>
        <!-- END Style Sheets -->
     </head>

<body>

<?php

	
}

function lp_html_footer() {
?>

</body>
</html>

<?php

}

// FIXME: tpl/login-form.tpl.php
function lp_login_form($error_msg = NULL) {
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
?>
<form action="/" method="POST">

<?php if ($error_msg !== NULL) { ?>
<h1><?php echo $error_msg; ?> Please do try again.</h1>

<?php } else { ?>
<h1>Please log in</h1>

<?php } ?>

<img src="<?php echo $lp_config["image_page"]; ?>">

<p>
Username:	<input type="text" name="username" value="">
</p>

<p>
Password:	<input type="text" name="password" value="">
</p>

<input type="hidden" name="redirect_uri" value="<?php echo $_REQUEST{"redirect_uri"}; ?>">
<input type="hidden" name="nonce" value="<?php echo $nonce; ?>">

<input type="submit">

</form>

<?php

	lp_html_footer();
}


function lp_fatal_error($msg) {
	lp_html_header();
?>

	<h1>There has been a fatal error</h1>

	<p>While processing your request we encountered a fatal error:

	<?php echo $msg; ?></h1>

	<p>This error is very probably a temporary one. Please excuse the inconvenience and try again in a short while.</p>

	<?php
	
	lp_html_footer();

	exit(0);

}

