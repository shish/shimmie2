<?php
// set up and purify the environment
define("DEBUG", true);
define("SCORE_VERSION", 's2hack');
define("VERSION", 'trunk');

if(!file_exists("config.php")) {
	header("Location: install.php");
	exit;
}

require_once "core/util.inc.php";
version_check();
sanitise_environment();


try {
	// load base files
	$files = array_merge(glob("core/*.php"), glob("ext/*/main.php"));
	foreach($files as $filename) {
		require_once $filename;
	}


	// connect to the database
	$database = new Database();
	$database->db->fnExecute = '_count_execs';
	$config = new DatabaseConfig($database);


	// load the theme parts
	$_theme = $config->get_string("theme", "default");
	if(!file_exists("themes/$_theme")) $_theme = "default";
	require_once "themes/$_theme/page.class.php";
	require_once "themes/$_theme/layout.class.php";
	require_once "themes/$_theme/themelet.class.php";

	$themelets = glob("ext/*/theme.php");
	foreach($themelets as $filename) {
		require_once $filename;
	}

	$custom_themelets = glob("themes/$_theme/*.theme.php");
	if($custom_themelets) {
		$m = array();
		foreach($custom_themelets as $filename) {
			if(preg_match("/themes\/$_theme\/(.*)\.theme\.php/",$filename,$m)
					&& array_contains($themelets, "ext/{$m[1]}/theme.php")) {
				require_once $filename;
			}
		}
	}


	// start the page generation waterfall
	$page = new Page();
	$user = _get_user($config, $database);
	$context = new RequestContext();
	$context->page = $page;
	$context->user = $user;
	$context->database = $database;
	$context->config = $config;
	send_event(new InitExtEvent($context));
	send_event(_get_page_request($context));
	$context->page->display($context);


	// for databases which support transactions
	if($database->engine->name != "sqlite") {
		$database->db->CommitTrans(true);
	}
}
catch(Exception $e) {
	$version = VERSION;
	$message = $e->getMessage();
	header("HTTP/1.0 500 Internal Error");
	print <<<EOD
<html>
	<head>
		<title>Internal error - SCore-$version</title>
	</head>
	<body>
		<h1>Internal Error</h1>
		<p>$message
	</body>
</html>
EOD;
}
?>
