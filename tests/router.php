<?php

declare(strict_types=1);

namespace Shimmie2;

// custom routing for stand-alone mode, basically
// .htaccess for the built-in php web server
if (PHP_SAPI !== 'cli-server') {
    die('cli only');
}

// warehouse files
$matches = [];
if (preg_match('/\/_(images|thumbs)\/([0-9a-f]{2})([0-9a-f]{30}).*$/', $_SERVER["REQUEST_URI"], $matches)) {
    header('Content-Type: image/jpeg');
    header("Cache-control: public, max-age=86400");
    print(file_get_contents("data/$matches[1]/$matches[2]/$matches[2]$matches[3]"));
    return true;
}

// if file exists, serve it as normal
elseif (is_file("." . explode("?", $_SERVER["REQUEST_URI"])[0])) {
    return false;
}

// all other requests (use shimmie routing based on URL)
else {
    unset($matches);
    // PHP_SELF is very unreliable, but there's no(?) better way to know what
    // website subdirectory we're installed in - if we're using router.php, then
    // let's blindly assume that we're in the root directory.
    $_SERVER["PHP_SELF"] = "/index.php";
    $_GET['q'] = explode("?", $_SERVER["REQUEST_URI"])[0];
    // if we use a custom handler, we need to do our own access log
    error_log("{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} [???]: {$_GET['q']}");
    require_once "index.php";
}
