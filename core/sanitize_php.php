<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "core/urls.php";

/*
 * A small number of PHP-sanity things (eg don't silently ignore errors) to
 * be included right at the very start of index.php and tests/bootstrap.php
 */

function die_nicely(string $title, string $body, int $code = 0): void
{
    $data_href = get_base_href();
    print("<!DOCTYPE html>
<html lang='en'>
	<head>
		<title>Shimmie</title>
		<link rel='shortcut icon' href='$data_href/ext/static_files/static/favicon.ico'>
		<link rel='stylesheet' href='$data_href/ext/static_files/style.css' type='text/css'>
		<link rel='stylesheet' href='$data_href/ext/static_files/installer.css' type='text/css'>
	</head>
	<body>
		<div id='installer'>
		    <h1>Shimmie</h1>
		    <h3>$title</h3>
			<div class='container'>
			    $body
			</div>
		</div>
    </body>
</html>");
    if ($code != 0) {
        http_response_code(500);
    }
    exit($code);
}

$min_php = "8.1";
if (version_compare(phpversion(), $min_php, ">=") === false) {
    die_nicely("Not Supported", "
        Shimmie does not support versions of PHP lower than $min_php
        (PHP reports that it is version ".phpversion().").
    ", 1);
}

# ini_set('zend.assertions', '1');  // generate assertions
ini_set('assert.exception', '1');  // throw exceptions when failed
set_error_handler(function ($errNo, $errStr) {
    // Should we turn ALL notices into errors? PHP allows a lot of
    // terrible things to happen by default...
    if (str_starts_with($errStr, 'Use of undefined constant ')) {
        throw new \Exception("PHP Error#$errNo: $errStr");
    } else {
        return false;
    }
});

ob_start();

if (PHP_SAPI === 'cli' || PHP_SAPI == 'phpdbg') {
    if (isset($_SERVER['REMOTE_ADDR'])) {
        die("CLI with remote addr? Confused, not taking the risk.");
    }
    $_SERVER['REMOTE_ADDR'] = "0.0.0.0";
    $_SERVER['HTTP_HOST'] = "cli-command";
}
