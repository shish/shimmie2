<?php
// custom routing for stand-alone mode
if(PHP_SAPI !== 'cli-server') die('cli only');

// warehouse files
$matches = array();
if(preg_match('/\/_(images|thumbs)\/([0-9a-f]{32}).*$/', $_SERVER["REQUEST_URI"], $matches)) {
	header('Content-Type: image/jpeg');
	print(file_get_contents(warehouse_path($matches[1], $matches[2])));
	return true;
}
unset($matches);

// use the default handler (serve static files, interpret php files)
if(preg_match('/\.(?:png|jpg|jpeg|gif|css|js|php)(\?.*)?$/', $_SERVER["REQUEST_URI"])) {
	return false;
}

// all other requests (use shimmie routing based on URL)
$_SERVER["PHP_SELF"] = '/';
$_GET['q'] = $_SERVER["REQUEST_URI"];
require_once "index.php";
