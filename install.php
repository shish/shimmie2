<?php ob_start(); ?>
<html>
<!--
 - install.php (c) Shish 2007
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
		<style>
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
			web server is mis-configured.
			<p>If you've installed a web server on your desktop PC, you probably
			want to visit <a href="http://localhost/">the local web server</a>.
		</div>
		<div style="display: none;">
			<PLAINTEXT>
<?php }
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

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
	include "config.php";
	if($_SESSION['dsn'] == DATABASE_DSN || $_POST['dsn'] == DATABASE_DSN) {
		if($_POST['dsn']) {$_SESSION['dsn'] = $_POST['dsn'];}

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
			echo "<h3>Database quick  fix for User deletion</h3>";
			echo "just a database fix for those who instaled shimmie before 2012 january the 22rd.<br>";
			echo "Note: some things needs to be done manually, to work properly.<br>";
			echo "WARNING: ONLY  PROCEEDS IF YOU KNOW WHAT YOU ARE DOING!";
			echo "
				<form action='install.php?action=Database_user_deletion_fix' method='POST'>
					<input type='submit' value='go!'>
				</form>
			";

			echo "<h3>Log Out</h3>";
			echo "
				<form action='install.php?action=logout' method='POST'>
					<input type='submit' value='Leave'>
				</form>
			";
		}
		else if($_GET["action"] == "logout") {
			session_destroy();
		}
		else if($_GET["action"] == "Database_user_deletion_fix") {
			Database_user_deletion_fix();
		}
	} else {
		echo "
			<h3>Login</h3>
			Enter the database DSN exactly as in config.php (ie, as originally
			installed) to access advanced recovery tools:

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
require_once "core/compat.inc.php";
require_once "core/database.class.php";

do_install();

// utilities {{{
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
		global $database_dsn;
		$database_dsn = "{$_POST['database_type']}:user={$_POST['database_user']};password={$_POST['database_password']};host={$_POST['database_host']};dbname={$_POST['database_name']}";
		install_process();
	}
	else if(file_exists("auto_install.conf")) {
		install_process(trim(file_get_contents("auto_install.conf")));
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
			The username provided must have access to create tables within the database.
			
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
			admin SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
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
		$db->execute("INSERT INTO config(name, value) VALUES('db_version', 8)");
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
	
		$db->execute("INSERT INTO users(name, pass, joindate, admin) VALUES(:name, :pass, now(), :admin)", Array("name" => 'Anonymous', "pass" => null, "admin" => 'N'));
		$db->execute("INSERT INTO config(name, value) VALUES(:name, :value)", Array("name" => 'anon_id', "value" => $db->get_last_insert_id()));

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
		print "Shimmie needs three folders in it's directory, 'images', 'thumbs', and 'data',
		       and they need to be writable by the PHP user (if you see this error,
			   if probably means the folders are owned by you, and they need to be
			   writable by the web server).
			   <p>Once you have created these folders, hit 'refresh' to continue.";
		exit;
	}
} // }}}
function write_config() { // {{{
	global $database_dsn;
	$file_content = "<"+"?php\n"+
	"define('DATABASE_DSN', '$database_dsn');\n"+
	"?"+">";
	
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

function Database_user_deletion_fix() {
	try {
		require_once "core/database.class.php";
		$db = new Database();
		
		echo "Fixing user_favorites table....";
		
		($db->Execute("ALTER TABLE user_favorites ENGINE=InnoDB;")) ? print_r("ok<br>") : print_r("failed<br>");
		echo "adding Foreign key to user ids...";
		
		($db->Execute("ALTER TABLE user_favorites ADD CONSTRAINT foreign_user_favorites_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;"))? print_r("ok<br>"):print_r("failed<br>");
		echo "cleaning, the table from deleted image favorites...<br>";
		
		$rows = $db->get_all("SELECT * FROM user_favorites WHERE image_id NOT IN ( SELECT id FROM images );");
		
		foreach( $rows as $key => $value)
			$db->Execute("DELETE FROM user_favorites WHERE image_id = :image_id;", array("image_id" => $value["image_id"]));
		
		echo "adding forign key to image ids...";
		
		($db->Execute("ALTER TABLE user_favorites ADD CONSTRAINT user_favorites_image_id FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE;"))? print_r("ok<br>"):print_r("failed<br>");
		
		echo "adding foreign keys to private messages...";
		
		($db->Execute("ALTER TABLE private_message 
		ADD CONSTRAINT foreign_private_message_from_id FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
		ADD CONSTRAINT foreign_private_message_to_id FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE;")) ? print_r("ok<br>"):print_r("failed<br>");
		
		echo "Just one more step...which you need to do manually:<br>";
		echo "You need to go to your database and Delete the foreign key on the owner_id in the images table.<br><br>";
		echo "<a href='http://www.justin-cook.com/wp/2006/05/09/how-to-remove-foreign-keys-in-mysql/'>How to remove foreign keys</a><br><br>";
		echo "and finally execute this querry:<br><br>";
		echo "ALTER TABLE images ADD CONSTRAINT foreign_images_owner_id FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT;<br><br>";
		echo "if this is all sucesfull you are done!";

	}
	catch (PDOException $e)
	{
		// FIXME: Make the error message user friendly
		exit($e->getMessage());
	}
}
?>
	</body>
</html>
