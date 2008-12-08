<?php
// set up and purify the environment
define("DEBUG", false);
define("VERSION", '2.2.3-svn');

require_once "core/util.inc.php";

version_check();
sanitise_environment();


// load base files
$files = array_merge(glob("core/*.php"), glob("ext/*/main.php"));
foreach($files as $filename) {
	require_once $filename;
}


// connect to database
$database = new Database();
$database->db->fnExecute = '_count_execs';
$config = new Config($database);


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
$user = _get_user();
send_event(new InitExtEvent());
send_event(_get_page_request($page, $user));
$page->display();


// for databases which support transactions
$database->db->CommitTrans(true);
?>
