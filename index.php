<?php
error_reporting(E_ALL);
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

if(version_compare(PHP_VERSION, "5.0.0") == -1) {
	print <<<EOD
Currently Shimmie 2 doesn't support versions of PHP lower than 5.0.0. Please
either upgrade your PHP, or tell Shish that PHP 4 support is a big deal for
you...
<!--
This version of Shimmie does not support versions lower than 5.0.0, however
you can create a version that does by using the u_create_monolith.php script.
This will read all the files in core/, events/ and ext/, strip the PHP 5 bits
out, and write a file called monolith.php. Monolith contains all the core
Shimmie code (not themes or config files), and can be used as a replacement
for index.php.
-->
EOD;
	exit;
}

$files = array_merge(glob("core/*.php"), glob("core/*/*.php"), glob("ext/*.php"));

foreach($files as $filename) {
	require_once $filename;
}

$database = new Database();
$database->db->fnExecute = 'CountExecs';
$config = new Config();
$page = new Page();
$user = get_user();
send_event(new InitExtEvent());
send_event(get_page_request());
$page->display();
?>
