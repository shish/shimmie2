<?php
// custom routing for stand-alone mode
if (PHP_SAPI !== 'cli-server') {
    die('cli only');
}

// warehouse files
@include_once "data/config/shimmie.conf.php";
@include_once "data/config/extensions.conf.php";
require_once "core/sys_config.php";
require_once "core/polyfills.php";
require_once "core/util.php";

$matches = [];
if (preg_match('/\/_(images|thumbs)\/([0-9a-f]{32}).*$/', $_SERVER["REQUEST_URI"], $matches)) {
    header('Content-Type: image/jpeg');
    header("Cache-control: public, max-age=86400");
    print(file_get_contents(warehouse_path($matches[1], $matches[2])));
    return true;
}

// use the default handler (serve static files, interpret php files)
elseif (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|php)(\?.*)?$/', $_SERVER["REQUEST_URI"])) {
    if (!preg_match('/register\.php(\?.*)?$/', $_SERVER["REQUEST_URI"])) {
        return false;
    }
}
unset($matches);

// all other requests (use shimmie routing based on URL)
$_GET['q'] = explode("?", $_SERVER["REQUEST_URI"])[0];
error_log($_GET['q']);  // if we use a custom handler, we need to do our own access log
require_once "index.php";
