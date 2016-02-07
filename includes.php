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


