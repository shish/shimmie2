<?php ob_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!--
 - install.php (c) Shish et all. 2007-2012
 -
 - Initialise the database, check that folder
 - permissions are set properly, set an admin
 - account.
 -
 - This file should be independant of the database
 - and other such things that aren't ready yet
-->
	<head>
		<title>Shimmie Installation</title>
		<link rel="shortcut icon" href="/favicon.ico" />
		<style type="text/css">
			BODY {background: #EEE;font-family: "Arial", sans-serif;font-size: 14px;}
			H1, H3 {border: 1px solid black;background: #DDD;text-align: center;}
			H1 {margin-top: 0px;margin-bottom: 0px;padding: 2px;}
			H3 {margin-top: 32px;padding: 1px;}
			FORM {margin: 0px;}
			A {text-decoration: none;}
			A:hover {text-decoration: underline;}
			#block {width: 512px; margin: auto; margin-top: 64px;}
			#iblock {width: 512px; margin: auto; margin-top: 16px;}
			TD INPUT {width: 350px;}
		</style>
	</head>
	<body>
<?php if(false) { ?>
		<div id="block">
			<h1>Install Error</h1>
			<p>Shimmie needs to be run via a web server with PHP support -- you
			appear to be either opening the file from your hard disk, or your
			web server is mis-configured.</p>
			<p>If you've installed a web server on your desktop PC, you probably
			want to visit <a href="http://localhost/">the local web server</a>.<br/><br/>
			</p>
		</div>
		<div style="display: none;">
			<PLAINTEXT>
<?php }
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

/*
 * Compute the path to the folder containing "install.php" and 
 * store it as the 'Shimmie Root' folder for later on.
 *
 * Example:
 *	__SHIMMIE_ROOT__ = '/var/www/shimmie2/'
 *
 */
define('__SHIMMIE_ROOT__', trim( remove_trailing_slash( dirname(__FILE__) ) ) . '/' ); 

// Pull in necessary files
require_once __SHIMMIE_ROOT__."core/util.inc.php";
require_once __SHIMMIE_ROOT__."core/exceptions.class.php";
require_once __SHIMMIE_ROOT__."core/database.class.php";

/*
 * This file lets anyone destroy the database -- disable it
 * as soon as the admin is done installing for the first time
 */
if(is_readable("config.php")) {
	session_start();
?>
		<div id="iblock">
			<h1>Shimmie Repair Console</h1>
<?php
	// Load the config
	require_once __SHIMMIE_ROOT__."config.php";			// Load user/site specifics First
	require_once __SHIMMIE_ROOT__."core/default_config.inc.php";	// Defaults for the rest.

	if (
	      ( array_key_exists('dsn', $_SESSION) && $_SESSION['dsn'] === DATABASE_DSN ) ||
	      ( array_key_exists('dsn', $_POST)    && $_POST['dsn']    === DATABASE_DSN )
	   )
	{
		if ( array_key_exists('dsn', $_POST) && !empty($_POST['dsn']) )
		{
		    $_SESSION['dsn'] = $_POST['dsn'];
		}

		if(empty($_GET["action"])) {
			echo "<h3>Basic Checks</h3>";
			echo "If these checks fail, something is broken; if they all pass, ";
			echo "something <i>might</i> be broken, just not checked for...";
			eok("Images writable", is_writable("images"));
			eok("Thumbs writable", is_writable("thumbs"));
			eok("Data writable", is_writable("data"));

			/*
			echo "<h3>New Database DSN</h3>";
			echo "
				<form action='install.php?action=newdsn' method='POST'>
					<center>
						<table>
							<tr><td>Database:</td><td><input type='text' name='new_dsn' size='40'></td></tr>
							<tr><td colspan='2'><center><input type='submit' value='Go!'></center></td></tr>
						</table>
					</center>
				</form>
			";
			*/

			echo "<h3>Log Out</h3>";
			echo "
				<form action='install.php?action=logout' method='POST'>
					<input type='submit' value='Leave'>
				</form>
			";
		}
		else if($_GET["action"] == "logout") {
			session_destroy();
			echo "<h3>Logged Out</h3><p>You have been logged out.</p><a href='index.php'>Main Shimmie Page</a>";
		}
	} else {
		echo "
			<h3>Login</h3>
			<p>Enter the database DSN exactly as in config.php (ie, as originally installed) to access advanced recovery tools:</p>

			<form action='install.php' method='POST'>
				<center>
					<table>
						<tr><td>Database:</td><td><input type='text' name='dsn' size='40'></td></tr>
						<tr><td colspan='2'><center><input type='submit' value='Go!'></center></td></tr>
					</table>
				</center>
			</form>
		";
	}
	echo "\t\t</div>";
	exit;
}

do_install();

// utilities {{{

/**
  * Strips off any kind of slash at the end so as to normalise the path.
  * @param string $path    Path to normalise.
  * @return string         Path without trailing slash.
  */
function remove_trailing_slash($path) {
	if ((substr($path, -1) === '/') || (substr($path, -1) === '\\')) {
		return substr($path, 0, -1);
	} else {
		return $path;
	}
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

function check_im_version() {
	if(!ini_get('safe_mode')) {
		$convert_check = exec("convert");
	}
	return (empty($convert_check) ? 0 : 1);
}

function eok($name, $value) {
	echo "<br>$name ... ";
	if($value) {
		echo "<font color='green'>ok</font>\n";
	}
	else {
		echo "<font color='red'>failed</font>\n";
	}
}
// }}}
function do_install() { // {{{
	if(isset($_POST['database_type']) && isset($_POST['database_host']) && isset($_POST['database_user']) && isset($_POST['database_name'])) {
		$database_dsn = "{$_POST['database_type']}:user={$_POST['database_user']};password={$_POST['database_password']};host={$_POST['database_host']};dbname={$_POST['database_name']}";
		define('DATABASE_DSN', $database_dsn);
		install_process();
	}
	else if(file_exists("auto_install.conf")) {
		define('DATABASE_DSN', trim(file_get_contents("auto_install.conf")));
		install_process();
		unlink("auto_install.conf");
	}
	else {
		begin();
	}
} // }}}
function begin() { // {{{
	$err = "";
	$thumberr = "";
	$dberr = "";

	if(check_gd_version() == 0 && check_im_version() == 0) {
		$thumberr = "<p>PHP's GD extension seems to be missing, ".
		      "and imagemagick's \"convert\" command cannot be found - ".
			  "no thumbnailing engines are available.";
	}

	if(!function_exists("mysql_connect")) {
		$dberr = "<p>PHP's MySQL extension seems to be missing; you may ".
				"be able to use an unofficial alternative, checking ".
				"for libraries...";
		if(!function_exists("pg_connect")) {
			$dberr .= "<br>PgSQL is missing";
		}
		else {
			$dberr .= "<br>PgSQL is available";
		}
		if(!function_exists("sqlite_open")) {
			$dberr .= "<br>SQLite is missing";
		}
		else {
			$dberr .= "<br>SQLite is available";
		}
	}

	if($thumberr || $dberr) {
		$err = "<h3>Error</h3>";
	}

	print <<<EOD
		<div id="iblock">
			<h1>Shimmie Installer</h1>

			$err
			$thumberr
			$dberr

			<h3>Database Install</h3>
			<form action="install.php" method="POST">
				<center>
					<table>
						<tr>
							<td>Type:</td>
							<td><select name="database_type">
								<option value="mysql" selected>MySQL</option>
								<option value="pgsql">PostgreSQL</option>
								<option value="sqlite">SQLite</option>
							</td>
						</tr>
						<tr>
							<td>Host:</td>
							<td><input type="text" name="database_host" size="40" value="localhost"></td>
						</tr>
						<tr>
							<td>Username:</td>
							<td><input type="text" name="database_user" size="40"></td>
						</tr>
						<tr>
							<td>Password:</td>
							<td><input type="password" name="database_password" size="40"></td>
						</tr>
						<tr>
							<td>Name:</td>
							<td><input type="text" name="database_name" size="40" value="shimmie"></td>
						</tr>
						<tr><td colspan="2"><center><input type="submit" value="Go!"></center></td></tr>
					</table>
				</center>
			</form>

			<h3>Help</h3>
					
			<p>Please make sure the database you have chosen exists and is empty.<br>
			The username provided must have access to create tables within the database.</p>
			
		</div>
EOD;
} // }}}
function install_process() { // {{{
	build_dirs();
	create_tables();
	insert_defaults();
	write_config();
	
	header("Location: index.php");
} // }}}
function create_tables() { // {{{
	try {
		$db = new Database();
		
		$db->create_table("aliases", "
			oldtag VARCHAR(128) NOT NULL PRIMARY KEY,
			newtag VARCHAR(128) NOT NULL,
			INDEX(newtag)
		");
		$db->create_table("config", "
			name VARCHAR(128) NOT NULL PRIMARY KEY,
			value TEXT
		");
		$db->create_table("users", "
			id SCORE_AIPK,
			name VARCHAR(32) UNIQUE NOT NULL,
			pass CHAR(32),
			joindate SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			class VARCHAR(32) NOT NULL DEFAULT 'user',
			email VARCHAR(128)
		");
		$db->create_table("images", "
			id SCORE_AIPK,
			owner_id INTEGER NOT NULL,
			owner_ip SCORE_INET NOT NULL,
			filename VARCHAR(64) NOT NULL,
			filesize INTEGER NOT NULL,
			hash CHAR(32) UNIQUE NOT NULL,
			ext CHAR(4) NOT NULL,
			source VARCHAR(255),
			width INTEGER NOT NULL,
			height INTEGER NOT NULL,
			posted SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			locked SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
			INDEX(owner_id),
			INDEX(width),
			INDEX(height),
			CONSTRAINT foreign_images_owner_id FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
		");
		$db->create_table("tags", "
			id SCORE_AIPK,
			tag VARCHAR(64) UNIQUE NOT NULL,
			count INTEGER NOT NULL DEFAULT 0
		");
		$db->create_table("image_tags", "
			image_id INTEGER NOT NULL,
			tag_id INTEGER NOT NULL,
			INDEX(image_id),
			INDEX(tag_id),
			UNIQUE(image_id, tag_id),
			CONSTRAINT foreign_image_tags_image_id FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
			CONSTRAINT foreign_image_tags_tag_id FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
		");
		$db->execute("INSERT INTO config(name, value) VALUES('db_version', 11)");
	}
	catch (PDOException $e)
	{
		// FIXME: Make the error message user friendly
		exit($e->getMessage());
	}
} // }}}
function insert_defaults() { // {{{
	try {
		$db = new Database();
	
		$db->execute("INSERT INTO users(name, pass, joindate, class) VALUES(:name, :pass, now(), :class)", Array("name" => 'Anonymous', "pass" => null, "class" => 'anonymous'));
		$db->execute("INSERT INTO config(name, value) VALUES(:name, :value)", Array("name" => 'anon_id', "value" => $db->get_last_insert_id('users_id_seq')));

		if(check_im_version() > 0) {
			$db->execute("INSERT INTO config(name, value) VALUES(:name, :value)", Array("name" => 'thumb_engine', "value" => 'convert'));
		}
	}
	catch (PDOException $e)
	{
		// FIXME: Make the error message user friendly
		exit($e->getMessage());
	}
} // }}}
function build_dirs() { // {{{
	// *try* and make default dirs. Ignore any errors --
	// if something is amiss, we'll tell the user later
	if(!file_exists("images")) @mkdir("images");
	if(!file_exists("thumbs")) @mkdir("thumbs");
	if(!file_exists("data")  ) @mkdir("data");
	if(!is_writable("images")) @chmod("images", 0755);
	if(!is_writable("thumbs")) @chmod("thumbs", 0755);
	if(!is_writable("data")  ) @chmod("data", 0755);

	if(
			!file_exists("images") || !file_exists("thumbs") || !file_exists("data") ||
			!is_writable("images") || !is_writable("thumbs") || !is_writable("data")
	) {
		print "<p>Shimmie needs three folders in it's directory, 'images', 'thumbs', and 'data',
		       and they need to be writable by the PHP user.</p>
			   <p>If you see this error, if probably means the folders are owned by you, and they need to be
			   writable by the web server.</p>
			   <p>PHP reports that it is currently running as user: ".$_ENV["USER"]." (". $_SERVER["USER"] .")</p>
			   <p>Once you have created these folders and/or changed the ownership of the shimmie folder, hit 'refresh' to continue.</p>";
		exit;
	}
} // }}}
function write_config() { // {{{
	$file_content = '<' . '?php' . "\n" .
			"define('DATABASE_DSN', '".DATABASE_DSN."');\n" .
			'?' . '>';
	
	if(is_writable("./") && file_put_contents("config.php", $file_content)) {
		assert(file_exists("config.php"));
	}
	else {
		$h_file_content = htmlentities($file_content);
		print <<<EOD
		The web server isn't allowed to write to the config file; please copy
	    the text below, save it as 'config.php', and upload it into the shimmie
	    folder manually. Make sure that when you save it, there is no whitespace
		before the "&lt;?php" or after the "?&gt;"

		<p><textarea cols="80" rows="2">$file_content</textarea>
						
		<p>One done, <a href='index.php'>Continue</a>
EOD;
		exit;
	}
} // }}}
?>
	</body>
</html>
