<?php if(false) { ?>
<html>
	<head>
		<title>Error</title>
		<style>
BODY {background: #EEE;font-family: "Arial", sans-serif;font-size: 14px;}
H1, H3 {border: 1px solid black;background: #DDD;text-align: center;}
H1 {margin-top: 0px;margin-bottom: 0px;padding: 2px;}
H3 {margin-top: 32px;padding: 1px;}
FORM {margin: 0px;}
A {text-decoration: none;}
A:hover {text-decoration: underline;}
#block {width: 512px; margin: auto; margin-top: 64px;}
		</style>
	</head>
	<body>
		<div id="block">
			<h1>Install Error</h1>
			<p>Shimmie needs to be run via a web server with PHP support -- you
			appear to be either opening the file from your hard disk, or your
			web server is mis-configured.
			<p>If you've installed a web server on your desktop PC, you probably
			want to visit <a href="http://localhost/">the local web server</a>.
			<p>For more help with installation, visit
			<a href="http://trac.shishnet.org/shimmie2/wiki/Guides/Admin/Install">the
			documentation wiki</a>.
		</div>
		<div style="display: none;">
<?php }
/*
 * install.php (c) Shish 2007
 *
 * Initialise the database, check that folder
 * permissions are set properly, set an admin
 * account.
 *
 * This file should be independant of the database
 * and other such things that aren't ready yet
 */

// FIXME: should be called from index
do_install();
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

// utilities {{{
function installer_write_file($fname, $data) {
	$fp = fopen($fname, "w");
	if(!$fp) return false;
	
	fwrite($fp, $data);
	fclose($fp);
	
	return true;
}

function check_gd_version() {
	$gdversion = 0;

	if (function_exists('gd_info')){
		$gd_info = gd_info();
		if (substr_count($gd_info['GD Version'], '2.')) {
			$gdversion = 2;
		} else if (substr_count($gd_info['GD Version'], '1.')) {
			$gdversion = 1;
		}
	}

	return $gdversion;
}
// }}}
// init {{{
function do_install() {
	/*
	 * This file lets anyone destroy the database -- disable it
	 * as soon as the admin is done installing for the first time
	 */
	if(is_readable("config.php")) {
		echo "'config.php' exists -- install function is disabled";
		exit;
	}
	require_once "lib/adodb/adodb.inc.php";

	session_start(); // hold temp stuff in session

	$stage = isset($_GET['stage']) ? $_GET['stage'] : "begin";

	switch($stage) {
		default: begin(); break;
		case 'install': install_process(); break;
	}
}


function begin() {
	if(check_gd_version() == 0) {
		$gd = "<h3>Error</h3>\nPHP's GD extension seems to be missing; ".
		      "you can live without it if you have imagemagick installed...";
	}
	else {
		$gd = "";
	}

	print <<<EOD
<html>
	<head>
		<title>Shimmie2 Installer</title>
		<style>
BODY {background: #EEE;font-family: "Arial", sans-serif;font-size: 14px;}
H1, H3 {border: 1px solid black;background: #DDD;text-align: center;}
H1 {margin-top: 0px;margin-bottom: 0px;padding: 2px;}
H3 {margin-top: 32px;padding: 1px;}
FORM {margin: 0px;}
A {text-decoration: none;}
A:hover {text-decoration: underline;}
#iblock {width: 512px; margin: auto; margin-top: 16px;}
TD INPUT {width: 100%}
		</style>
	</head>
	<body>
		<div id="iblock">
			<h1>Shimmie Installer</h1>

			$gd

			<h3>Install</h3>
			<form action="install.php?stage=install" method="POST">
				<center>
					<table>
						<tr><td>Database:</td><td><input type="text" name="database_dsn" size="40"></td></tr>
						<tr><td>Admin Name:</td><td><input type="text" name="admin_name" size="40"></td></tr>
						<tr><td>Admin Pass:</td><td><input type="password" name="admin_pass" size="40"></td></tr>
						<tr><td colspan="2"><input type="submit" value="Go!"></td></tr>
					</table>
				</center>
			</form>

			<h3>Help</h3>
					
			<p>Databases should be specified like so:
			<br>ie: <code>protocol://username:password@host/database?options</code>
			<br>eg: <code>mysql://shimmie:pw123@localhost/shimmie?persist</code>
			
			<p>For more help with installation, visit
			<a href="http://trac.shishnet.org/shimmie2/wiki/Guides/Admin/Install">the
			documentation wiki</a>.
		</div>
	</body>
</html>
EOD;
}
// }}}
// common {{{
function set_admin_cookie($admin_name, $admin_pass) { // {{{
	$addr = $_SERVER['REMOTE_ADDR'];
	$hash = md5(strtolower($admin_name) . $admin_pass);
	setcookie("shm_user", $admin_name, time()+60*60*24*365);
	setcookie("shm_session", md5($hash.$addr), time()+60*60*24*7, "/");
} // }}}
function create_tables($dsn) { // {{{
	$db = NewADOConnection($dsn);
	if(!$db) {
		die("Couldn't connect to \"$dsn\"");
	}
	else {
		if(substr($dsn, 0, 5) == "mysql") {
			if(create_tables_mysql($db)) {
				$_SESSION['tables_created'] = true;
			}
		}
		else if(substr($dsn, 0, 5) == "pgsql" || substr($dsn, 0, 8) == "postgres") {
			if(create_tables_pgsql($db)) {
				$_SESSION['tables_created'] = true;
			}
		}
		else if(substr($dsn, 0, 6) == "sqlite") {
			if(create_tables_sqlite($db)) {
				$_SESSION['tables_created'] = true;
			}
		}
		else {
			die("This database format isn't currently supported. Please use either MySQL, PostgreSQL, or SQLite.");
		}

		if(!isset($_SESSION['tables_created']) || !$_SESSION['tables_created']) {
			die("Error creating tables");
		}
	}
	$db->Close();
} // }}}
function build_dirs() { // {{{
	if(!file_exists("images")) @mkdir("images"); // *try* and make default dirs. Ignore any errors -- 
	if(!file_exists("thumbs")) @mkdir("thumbs"); // if something is amiss, we'll tell the user later
	if(!file_exists("data")) @mkdir("data");

	if(
			((!file_exists("images") || !file_exists("thumbs") || !file_exists("data")) && !is_writable("./")) ||
			(!is_writable("images") || !is_writable("thumbs") || !is_writable("data"))
	) {
		print "Shimmie needs three folders in it's directory, 'images', 'thumbs', and 'data',
		       and they need to be writable by the PHP user (if you see this error,
			   if probably means the folders are owned by you, and they need to be
			   writable by the web server).
			   
			   <p>Once you have created these folders, hit 'refresh' to continue.";
		exit;
	}
	else {
		assert(file_exists("images") && is_writable("images"));
		assert(file_exists("thumbs") && is_writable("thumbs"));
		assert(file_exists("data") && is_writable("data"));

		if(!file_exists("images/ff")) {
			for($i=0; $i<256; $i++) {
				mkdir(sprintf("images/%02x", $i));
				mkdir(sprintf("thumbs/%02x", $i));
			}
		}
	}
} // }}}
function write_config($dsn) { // {{{
	$file_content = "<?php \$database_dsn='$dsn'; ?>";
	
	if(is_writable("./") && installer_write_file("config.php", $file_content)) {
		assert(file_exists("config.php"));
		session_destroy();
	}
	else {
		$h_file_content = htmlentities($file_content);
		print <<<EOD
<html>
	<head><title>Shimmie2 Installer</title></head>
	<body>
		The web server isn't allowed to write to the config file; please copy
	    the text below, save it as 'config.php', and upload it into the shimmie
	    folder manually. Make sure that when you save it, there is no whitespace
		before the "&lt;?php" or after the "?&gt;"

		<p><textarea cols="80" rows="2">$file_content</textarea>
						
		<p>One done, <a href='index.php?q=setup'>Continue</a>
	</body>
</html>
EOD;
		session_destroy();
		exit;
	}
} // }}}
// }}}
// install {{{
function install_process() { // {{{
	if(!isset($_POST['database_dsn']) || !isset($_POST["admin_name"]) || !isset($_POST["admin_pass"])) {
		die("Install is missing some paramaters (database_dsn, admin_name, or admin_pass)");
	}
	else if(strlen($_POST["admin_name"]) < 1 || strlen($_POST["admin_pass"]) < 1) {
		die("Admin name and password must be at least one character each");
	}
	else {
		$database_dsn = $_POST['database_dsn'];
		$admin_name = $_POST["admin_name"];
		$admin_pass = $_POST["admin_pass"];
	}

	set_admin_cookie($admin_name, $admin_pass);
	create_tables($database_dsn);
	insert_defaults($database_dsn, $admin_name, $admin_pass);
	build_dirs();
	write_config($database_dsn);
	
	header("Location: index.php?q=setup");
} // }}}
function insert_defaults($dsn, $admin_name, $admin_pass) { // {{{
	$db = NewADOConnection($dsn);
	if(!$db) {
		die("Couldn't connect to \"$dsn\"");
	}
	else {
		$config_insert = $db->Prepare("INSERT INTO config(name, value) VALUES(?, ?)");
		$user_insert = $db->Prepare("INSERT INTO users(name, pass, joindate, admin) VALUES(?, ?, now(), ?)");

		if(!$db->GetOne("SELECT * FROM users WHERE name=?", Array('Anonymous'))) {
			$db->Execute($user_insert, Array('Anonymous', null, 'N'));
				
			$db->Execute("DELETE FROM config WHERE name=?", Array('anon_id'));
			$db->Execute($config_insert, Array('anon_id', $db->Insert_ID()));
		}
	
		# we can safely delete the user and recreate, hence changing the ID,
		# because insert_defaults is only called during first installation
		if($db->GetOne("SELECT * FROM users WHERE name=?", Array($admin_name))) {
			$db->Execute("DELETE FROM users WHERE name=?", Array($admin_name));
		}

		$admin_pass = md5(strtolower($admin_name).$admin_pass);
		$db->Execute($user_insert, Array($admin_name, $admin_pass, 'Y'));

		if(!ini_get('safe_mode')) {
			$convert_check = exec("convert");
			if(!empty($convert_check)) {
				$db->Execute($config_insert, Array('thumb_engine', 'convert'));
			}
		}
		
		$db->Close();
	}
} // }}}
// }}}


// table creation {{{
/*
 * Note: try and keep this as ANSI SQL compliant as possible,
 * so that we can (in theory) support other databases
 */
function create_tables_common($db, $auto_incrementing_id, $boolean, $true, $false, $ip) {
	$db->Execute("CREATE TABLE aliases (
		oldtag VARCHAR(255) NOT NULL PRIMARY KEY,
		newtag VARCHAR(255) NOT NULL
	)");

	$db->Execute("CREATE TABLE config (
		name VARCHAR(255) NOT NULL PRIMARY KEY,
		value TEXT
	)");

	$db->Execute("CREATE TABLE images (
		id $auto_incrementing_id,
		owner_id INTEGER NOT NULL,
		owner_ip $ip,
		filename VARCHAR(64) NOT NULL DEFAULT '',
		filesize INTEGER NOT NULL,
		hash CHAR(32) NOT NULL UNIQUE,
		ext CHAR(4) NOT NULL,
		source VARCHAR(255),
		width INTEGER NOT NULL,
		height INTEGER NOT NULL,
		posted TIMESTAMP NOT NULL
	)");

	$db->Execute("CREATE TABLE users (
		id $auto_incrementing_id,
		name VARCHAR(32) NOT NULL UNIQUE,
		pass CHAR(32),
		joindate DATETIME NOT NULL,
		enabled $boolean NOT NULL DEFAULT $true,
		admin $boolean NOT NULL DEFAULT $false,
		email VARCHAR(255)
	)");
	
	$db->Execute("CREATE TABLE layout (
		title VARCHAR(64) PRIMARY KEY NOT NULL,
		section VARCHAR(32) NOT NULL DEFAULT 'left',
		position INTEGER NOT NULL DEFAULT 50,
		visible $boolean DEFAULT $true
	)");

	$db->Execute("CREATE TABLE tags (
		id $auto_incrementing_id,
		tag VARCHAR(64) NOT NULL UNIQUE,
		count INTEGER NOT NULL DEFAULT 0
	)");
	$db->Execute("CREATE INDEX tags__count ON tags(count)");
	
	$db->Execute("CREATE TABLE image_tags (
		image_id INTEGER NOT NULL DEFAULT 0,
		tag_id INTEGER NOT NULL DEFAULT 0,
		UNIQUE (image_id, tag_id)
	)");
	$db->Execute("CREATE INDEX image_tags__tag_id ON image_tags(tag_id)");
	$db->Execute("CREATE INDEX image_tags__image_id ON image_tags(image_id)");
	

	$db->Execute("INSERT INTO config(name, value) VALUES(?, ?)", Array('db_version', 5));
}
function create_tables_mysql($db) {
	$db->StartTrans();
	$db->Execute("SET NAMES utf8");
	create_tables_common($db,
		"INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY",
		"ENUM('Y', 'N')", "'Y'", "'N'",
		"CHAR(15)"
	);
	return $db->CommitTrans();
}
function create_tables_pgsql($db) {
	$db->StartTrans();
	create_tables_common($db,
		"SERIAL NOT NULL PRIMARY KEY",
		"BOOLEAN", "True", "False",
		"INET"
	);
	return $db->CommitTrans();
}
function create_tables_sqlite($db) {
	$db->StartTrans();
	create_tables_common($db,
		"INTEGER AUTOINCREMENT PRIMARY KEY NOT NULL",
		"CHAR(1)", "'Y'", "'N'",
		"CHAR(15)"
	);
	return $db->CommitTrans();
}
// }}}
?>
