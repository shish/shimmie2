<?php
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
		case 'upgrade': upgrade_process(); break;
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
	<head><title>Shimmie2 Installer</title></head>
	<style>
BODY {
	background: #EEE;
	font-family: "Arial", sans-serif;
	font-size: 14px;
}
H1, H3 {
	border: 1px solid black;
	background: #DDD;
	text-align: center;
}
H1 {
	margin-top: 0px;
	margin-bottom: 0px;
	padding: 2px;
}
H3 {
	margin-top: 32px;
	padding: 1px;
}
TD {
	vertical-align: top;
	text-align: center;
}
FORM {margin: 0px;}
A {text-decoration: none;}
A:hover {text-decoration: underline;}
	</style>
	<body>
		<h1>Shimmie Installer</h1>

		$gd

		<h3>Note</h3>
		Shimmie is developed with MySQL, and support
		for it is included. Other databases <i>may</i> work,
		but you'll need to add the appropriate ADOdb
		drivers yourself
		
		<h3>Install</h3>
		<form action="install.php?stage=install" method="POST">
			<center>
				<table>
					<tr><td>Database</td><td><input type="text" name="database_dsn" size="50"></td></tr>
					<tr><td>Admin Name:</td><td><input type="text" name="admin_name" size="50"></td></tr>
					<tr><td>Admin Pass:</td><td><input type="password" name="admin_pass" size="50"></td></tr>
					<tr><td colspan="2"><input type="submit" value="Next"></td></tr>
				</table>
				
				<p>Databases should be specified like so:
				<br>ie: protocol://username:password@host/database?options
				<br>eg: mysql://shimmie:pw123@localhost/shimmie?persist
			</center>
		</form>
		
		<h3>Upgrade</h3>
		<form action="install.php?stage=upgrade" method="POST">
			<center>
				<table>
					<tr><td>Old Database:</td><td><input type="text" size="50" name="old_dsn"></td></tr>
					<tr><td>New Database:</td><td><input type="text" size="50" name="new_dsn"></td></tr>
					<tr><td>Old Data Folder:</td><td><input type="text" size="50" name="old_data"></td></tr>
					<tr><td colspan="2"><input type="submit" value="Next"></td></tr>
				</table>

				<p>Data folder is where the "images" and "thumbs" folders are stored
			</center>
		</form>
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
		if(create_tables_mysql($db)) {
			$_SESSION['tables_created'] = true;
		}
		else {
			die("Error creating tables");
		}
	}
	$db->Close();
} // }}}
function build_dirs() { // {{{
	if(!file_exists("images")) @mkdir("images"); // *try* and make default dirs. Ignore any errors -- 
	if(!file_exists("thumbs")) @mkdir("thumbs"); // if something is amiss, we'll tell the user later

	if(
			((!file_exists("images") || !file_exists("thumbs")) && !is_writable("./")) ||
			(!is_writable("images") || !is_writable("thumbs"))
	) {
		print "Shimmie needs two folders in it's directory, 'images' and 'thumbs',
		       and they need to be writable by the PHP user (if you see this error,
			   if probably means the folders are owned by you, and they need to be
			   writable by the web server).
			   
			   <p>Once you have created these folders, hit 'refresh' to continue.";
		exit;
	}
	else {
		assert(file_exists("images") && is_writable("images"));
		assert(file_exists("thumbs") && is_writable("thumbs"));

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
// upgrade {{{
function upgrade_process() { // {{{
	if(!isset($_POST['old_dsn']) || !isset($_POST["new_dsn"]) || !isset($_POST["old_data"])) {
		die("Install is missing some paramaters (old_dsn, new_dsn, or old_data)");
	}
	else {
		$old_dsn = $_POST['old_dsn'];
		$new_dsn = $_POST['new_dsn'];
		$old_data = $_POST['old_data'];
	}

	if(!is_readable($old_data)) {die("Can't find \"$old_data\"");}
	if(!is_readable("$old_data/images")) {die("Can't find \"$old_data/images\"");}
	if(!is_readable("$old_data/thumbs")) {die("Can't find \"$old_data/thumbs\"");}

	// set_admin_cookie($admin_name, $admin_pass);
	create_tables($new_dsn);
	build_dirs();
	move_data($old_dsn, $new_dsn, $old_data);
	write_config($new_dsn);
	
//	header("Location: index.php?q=setup");
	print "<p>If everything looks OK, <a href='index.php?q=user/login'>continue</a>";
} // }}}
function move_data($old_dsn, $new_dsn, $old_data) {
	print("<br>Upping PHP resource limits...");
	set_time_limit(600);
	ini_set("memory_limit", "32M");

	print("<br>Fetching old data...");
	$old_db = NewADOConnection($old_dsn);
	$old_db->SetFetchMode(ADODB_FETCH_ASSOC);
	# tmpfile & serialize?
	$anon_id = -1;
	$users = $old_db->GetAll("SELECT id, name, pass, joindate FROM users ORDER BY id");
	$admins = $old_db->GetCol("SELECT owner_id FROM user_configs WHERE name='isadmin' AND value='true'");
	$images = $old_db->GetAll("SELECT id, owner_id, owner_ip, filename, hash, ext FROM images ORDER BY id");
	$comments = $old_db->GetAll("SELECT id, image_id, owner_id, owner_ip, posted, comment FROM comments ORDER BY id");
	$tags = $old_db->GetAll("SELECT image_id, tag FROM tags");
	$old_db->Close();

	$new_db = NewADOConnection($new_dsn);
	$new_db->SetFetchMode(ADODB_FETCH_ASSOC);

	if($users) {
		print("<br>Moving users...");
		$new_db->Execute("DELETE FROM users");
		$new_db->Execute("
				INSERT INTO users(id, name, pass, joindate, enabled, admin, email)
				VALUES(?, ?, ?, ?, 'Y', 'N', '')", $users);
	}

	if($admins) {
		print("<br>Setting account flags");
		$new_db->Execute("UPDATE users SET admin='Y' WHERE id=?", array($admins));
	}

	if(true) {
		print("<br>Updating anonymous account...");
		$anon_id = $new_db->GetOne("SELECT id FROM users WHERE name='Anonymous'");
		if(!$anon_id) {
			print("<br><b>Warning</b>: 'Anonymous' not found; creating one");
			$new_db->Execute("INSERT INTO users(name, pass, joindate) VALUES ('Anonymous', NULL, now())");
			$anon_id = $new_db->Insert_ID();
		}

		$new_db->Execute("DELETE FROM config WHERE name=?", array("anon_id"));
		$new_db->Execute("INSERT INTO config(name, value) VALUES(?, ?)", array('anon_id', $anon_id));
	}

	if($images) {
		print("<br>Moving images...");
		$new_db->Execute("DELETE FROM images");
		$new_db->Execute("
				INSERT INTO images(id, owner_id, owner_ip, filename, hash, ext, filesize, width, height, source, posted)
				VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, NULL, now())", $images);

		print("<br>Setting orphan images to anonymous...");
		$orphans = $new_db->GetCol("
			SELECT images.id
			FROM images
			LEFT JOIN users ON users.id = images.owner_id
			WHERE isnull(users.name)");
		if($orphans) {
			foreach($orphans as $orphan) {
				$new_db->Execute("UPDATE images SET owner_id=? WHERE id=?", array($anon_id, $orphan));
			}
		}
	}

	if($comments) {
		print("<br>Moving comments...");

		// HAAAAAAAX!
		// the comments table is installed by an extension, so it won't be
		// ready when we need it...
		$new_db->Execute("DROP TABLE comments");
		$new_db->Execute("DELETE FROM config WHERE name=?", array('ext_comments_version'));

		$new_db->Execute("CREATE TABLE `comments` (
			`id` int(11) NOT NULL auto_increment,
			`image_id` int(11) NOT NULL,
			`owner_id` int(11) NOT NULL,
			`owner_ip` char(16) NOT NULL,
			`posted` datetime default NULL,
			`comment` text NOT NULL,
			PRIMARY KEY  (`id`),
			KEY `comments_image_id` (`image_id`)
		)");
		$new_db->Execute("INSERT INTO config(name, value) VALUES(?, ?)", array("ext_comments_version", 1));

		$new_db->Execute("DELETE FROM comments");
		$new_db->Execute("
				INSERT INTO comments(id, image_id, owner_id, owner_ip, posted, comment)
				VALUES (?, ?, ?, ?, ?, ?)", $comments);

		print("<br>Setting orphan comments to anonymous...");
		$orphans = $new_db->GetCol("
			SELECT comments.id
			FROM comments
			LEFT JOIN users ON users.id = comments.owner_id
			WHERE isnull(users.name)");
		if($orphans) {
			foreach($orphans as $orphan) {
				$new_db->Execute("UPDATE comments SET owner_id=? WHERE id=?", array($anon_id, $orphan));
			}
		}
	}

	if($tags) {
		print("<br>Moving tags..");
		$new_db->Execute("CREATE TABLE old_tags(image_id int, tag varchar(64))");
		$new_db->Execute("DELETE FROM old_tags");
		$new_db->Execute("INSERT INTO old_tags(image_id, tag) VALUES (?, ?)", $tags);
		
		$database->Execute("DELETE FROM tags");
		$database->Execute("INSERT INTO tags(tag) SELECT DISTINCT tag FROM old_tags");
		$database->Execute("DELETE FROM image_tags");
		$database->Execute("INSERT INTO image_tags(image_id, tag_id) SELECT old_tags.image_id, tags.id FROM old_tags JOIN tags ON old_tags.tag = tags.tag");
		$database->Execute("UPDATE tags SET count=(SELECT COUNT(image_id) FROM image_tags WHERE tag_id=tags.id GROUP BY tag_id)");
		$new_db->Execute("DROP TABLE tags_tmp");
	}

	print("<br>Moving files...");
	$result = $new_db->Execute("SELECT * FROM images");
	while(!$result->EOF) {
		$fields = $result->fields;

		$id = $fields['id'];
		$hash = $fields['hash'];
		$ext = $fields['ext'];
		$ab = substr($hash, 0, 2);

		if(file_exists("images/$ab/$hash")) {
			unlink("images/$ab/$hash");
		}

		$fname = "$old_data/images/$id.$ext";
		if(file_exists($fname)) {
			$size = filesize($fname);
			$sizekb = (int)($size/1024);
			$info = getimagesize($fname);
			if($info) {
				$width = $info[0];
				$height = $info[1];

				// print "<br>{$id}: {$width}x{$height}, {$sizekb}KB\n"; // noise
				$new_db->Execute("UPDATE images SET width=?, height=?, filesize=? WHERE id=?",
					array($width, $height, $size, $id));
			}
	
			copy("$old_data/thumbs/$id.jpg", "thumbs/$ab/$hash");
			copy("$old_data/images/$id.$ext", "images/$ab/$hash");
		}
		else {
			print "<br><b>Warning:</b> $fname not found; dropped from new database";
			$new_db->Execute("DELETE FROM images WHERE id=?", array($id));
		}
	
		$result->MoveNext();
	}

	$new_db->Close();
}
// }}}


// table creation {{{
/*
 * Note: try and keep this as ANSI SQL compliant as possible,
 * so that we can (in theory) support other databases
 */
function create_tables_mysql($db) {
	$db->StartTrans();

	$db->Execute("SET NAMES utf8"); // FIXME: mysql-specific :(
	
	$db->Execute("DROP TABLE IF EXISTS aliases");
	$db->Execute("CREATE TABLE aliases (
		oldtag varchar(255) NOT NULL,
		newtag varchar(255) NOT NULL,
		PRIMARY KEY (oldtag)
	)");

	$db->Execute("DROP TABLE IF EXISTS config");
	$db->Execute("CREATE TABLE config (
		name varchar(255) NOT NULL,
		value text,
		PRIMARY KEY (name)
	)");

	$db->Execute("DROP TABLE IF EXISTS images");
	$db->Execute("CREATE TABLE images (
		id int(11) NOT NULL auto_increment,
		owner_id int(11) NOT NULL default '0',
		owner_ip char(16) default NULL,
		filename varchar(64) NOT NULL default '',
		filesize int(11) NOT NULL default '0',
		hash char(32) NOT NULL default '',
		ext char(4) NOT NULL default '',
		source varchar(255),
		width int(11) NOT NULL,
		height int(11) NOT NULL,
		posted datetime NOT NULL,
		PRIMARY KEY (id),
		UNIQUE (hash)
	)");

	$db->Execute("DROP TABLE IF EXISTS tags");
	$db->Execute("CREATE TABLE tags (
		id int not null auto_increment primary key,
		tag varchar(64) not null unique,
		count int not null default 0,
		KEY tags_count(count)
	)");
	
	$db->Execute("DROP TABLE IF EXISTS image_tags");
	$db->Execute("CREATE TABLE image_tags (
		image_id int NOT NULL default 0,
		tag_id int NOT NULL default 0,
		UNIQUE KEY image_id_tag_id (image_id,tag_id),
		KEY tags_tag_id (tag_id),
		KEY tags_image_id (image_id)
	)");

	$db->Execute("DROP TABLE IF EXISTS users");
	$db->Execute("CREATE TABLE users (
		id int(11) NOT NULL auto_increment,
		name varchar(32) NOT NULL,
		pass char(32) default NULL,
		joindate datetime NOT NULL,
		enabled enum('N','Y') NOT NULL default 'Y',
		admin enum('N','Y') NOT NULL default 'N',
		email varchar(255) default NULL,
		PRIMARY KEY (id),
		UNIQUE (name)
	)");
	
	$db->Execute("DROP TABLE IF EXISTS layout");
	$db->Execute("CREATE TABLE layout (
		title varchar(64) primary key not null,
		section varchar(32) not null default \"left\",
		position int not null default 50,
		visible enum('Y', 'N') default 'Y' not null
	)");

	$db->Execute("INSERT INTO config(name, value) VALUES(?, ?)", Array('db_version', 5));

	return $db->CommitTrans();
}
// }}}
?>
