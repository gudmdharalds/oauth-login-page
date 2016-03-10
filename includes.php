<?php

/*
 * Include all the files we need, here in one
 * place. This was before in config.php, but
 * it really doesn't belong there as users
 * have to create those files themselves.
 * 
 * Note that this file is included from the
 * unit-tests.
 */

require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/misc.php");
require_once(__DIR__ . "/html.php");
require_once(__DIR__ . "/session.php");
require_once(__DIR__ . "/nonce.php");
require_once(__DIR__ . "/filters.php");


/*
 * Now load any .php files that
 * might have been put in "customizations"
 * folder.
 */

$customizations_dir_path = __DIR__ . "/customizations/";
$customizations_files = scandir($customizations_dir_path);

foreach ($customizations_files as $customizations_file_item) {
	$tmp = explode(".php", $customizations_file_item);

	if ((count($tmp) !== 2) && ($tmp[1] !== "")) {
		continue;
	}

	if (file_exists($customizations_dir_path . "/oauth2_calls.php")) {
		require_once($customizations_dir_path . "/oauth2_calls.php");
	}
}

unset($customizations_dir_path);
unset($customizations_files);
unset($tmp);


