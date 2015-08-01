<?php
// custom routing for stand-alone mode

if(php_sapi_name() != 'cli-server') {
	die('local testing only');
}

// warehouse files
$matches = array();
if(preg_match('/\/_(images|thumbs)\/([0-9a-f]{32}).*$/', $_SERVER["REQUEST_URI"], $matches)) {
	header('Content-Type: image/jpeg');
	print(file_get_contents(warehouse_path($matches[1], $matches[2])));
	return true;
}

// static files
if(preg_match('/\.(?:png|jpg|jpeg|gif|css|js)$/', $_SERVER["REQUEST_URI"])) {
	return false;
}

// all other requests
$_SERVER["PHP_SELF"] = '/';
$_GET['q'] = $_SERVER["REQUEST_URI"];
require_once "index.php";
