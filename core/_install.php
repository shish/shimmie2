<?php
/**
 * Shimmie Installer
 *
 * @package    Shimmie
 * @copyright  Copyright (c) 2007-2015, Shish et al.
 * @author     Shish [webmaster at shishnet.org], jgen [jeffgenovy at gmail.com]
 * @link       http://code.shishnet.org/shimmie2/
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Initialise the database, check that folder
 * permissions are set properly.
 *
 * This file should be independent of the database
 * and other such things that aren't ready yet
 */

// TODO: Rewrite the entire installer and make it more readable.

ob_start();

date_default_timezone_set('UTC');
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Shimmie Installation</title>
		<link rel="shortcut icon" href="ext/handle_static/static/favicon.ico">
		<link rel="stylesheet" href="lib/shimmie.css" type="text/css">
		<script type="text/javascript" src="vendor/bower-asset/jquery/dist/jquery.min.js"></script>
	</head>
	<body>
<?php if (false) { ?>
		<div id="installer">
			<h1>Install Error</h1>
			<div class="container">
				<p>Shimmie needs to be run via a web server with PHP support -- you
				appear to be either opening the file from your hard disk, or your
				web server is mis-configured and doesn't know how to handle PHP files.</p>
				<p>If you've installed a web server on your desktop PC, you probably
				want to visit <a href="http://localhost/">the local web server</a>.<br/><br/>
				</p>
			</div>
		</div>
		<pre style="display:none">
<?php } elseif (!file_exists("vendor/")) { ?>
		<div id="installer">
			<h1>Install Error</h1>
			<h3>Warning: Composer vendor folder does not exist!</h3>
			<div class="container">
				<p>Shimmie is unable to find the composer vendor directory.<br>
				Have you followed the composer setup instructions found in the <a href="https://github.com/shish/shimmie2#installation-development">README</a>?</>

				<p>If you are not intending to do any development with Shimmie, it is highly recommend you use one of the pre-packaged releases found on <a href="https://github.com/shish/shimmie2/releases">Github</a> instead.</p>
			</div>
		</div>
		<pre style="display:none">
<?php }

// Pull in necessary files
require_once "core/exceptions.php";
require_once "core/cacheengine.php";
require_once "core/dbengine.php";
require_once "core/database.php";

if (is_readable("data/config/shimmie.conf.php")) {
    die("Shimmie is already installed.");
}

do_install();

// utilities {{{
    // TODO: Can some of these be pushed into "core/???.inc.php" ?

function check_gd_version(): int
{
    $gdversion = 0;

    if (function_exists('gd_info')) {
        $gd_info = gd_info();
        if (substr_count($gd_info['GD Version'], '2.')) {
            $gdversion = 2;
        } elseif (substr_count($gd_info['GD Version'], '1.')) {
            $gdversion = 1;
        }
    }

    return $gdversion;
}

function check_im_version(): int
{
    $convert_check = exec("convert");

    return (empty($convert_check) ? 0 : 1);
}

function eok($name, $value)
{
    echo "<br>$name ... ";
    if ($value) {
        echo "<span style='color: green'>ok</span>\n";
    } else {
        echo "<span style='color: green'>failed</span>\n";
    }
}
// }}}

function do_install()
{ // {{{
    if (file_exists("data/config/auto_install.conf.php")) {
        require_once "data/config/auto_install.conf.php";
    } elseif (@$_POST["database_type"] == "sqlite") {
        $id = bin2hex(random_bytes(5));
        define('DATABASE_DSN', "sqlite:data/shimmie.{$id}.sqlite");
    } elseif (isset($_POST['database_type']) && isset($_POST['database_host']) && isset($_POST['database_user']) && isset($_POST['database_name'])) {
        define('DATABASE_DSN', "{$_POST['database_type']}:user={$_POST['database_user']};password={$_POST['database_password']};host={$_POST['database_host']};dbname={$_POST['database_name']}");
    } else {
        ask_questions();
        return;
    }

    define("CACHE_DSN", null);
    define("DEBUG_SQL", false);
    define("DATABASE_KA", true);
    install_process();
} // }}}

function ask_questions()
{ // {{{
    $warnings = [];
    $errors = [];

    if (check_gd_version() == 0 && check_im_version() == 0) {
        $errors[] = "
			No thumbnailers could be found - install the imagemagick
			tools (or the PHP-GD library, if imagemagick is unavailable).
		";
    } elseif (check_im_version() == 0) {
        $warnings[] = "
			The 'convert' command (from the imagemagick package)
			could not be found - PHP-GD can be used instead, but
			the size of thumbnails will be limited.
		";
    }

    if (!function_exists('mb_strlen')) {
        $errors[] = "
			The mbstring PHP extension is missing - multibyte languages
			(eg non-english languages) may not work right.
		";
    }

    $drivers = PDO::getAvailableDrivers();
    if (
        !in_array("mysql", $drivers) &&
        !in_array("pgsql", $drivers) &&
        !in_array("sqlite", $drivers)
    ) {
        $errors[] = "
			No database connection library could be found; shimmie needs
			PDO with either Postgres, MySQL, or SQLite drivers
		";
    }

    $db_m = in_array("mysql", $drivers)  ? '<option value="mysql">MySQL</option>' : "";
    $db_p = in_array("pgsql", $drivers)  ? '<option value="pgsql">PostgreSQL</option>' : "";
    $db_s = in_array("sqlite", $drivers) ? '<option value="sqlite">SQLite</option>' : "";

    $warn_msg = $warnings ? "<h3>Warnings</h3>".implode("\n<p>", $warnings) : "";
    $err_msg = $errors ? "<h3>Errors</h3>".implode("\n<p>", $errors) : "";

    print <<<EOD
		<div id="installer">
			<h1>Shimmie Installer</h1>

			<div class="container">
				$warn_msg
				$err_msg

				<h3>Database Install</h3>
				<form action="index.php" method="POST">
					<center>
						<table class='form'>
							<tr>
								<th>Type:</th>
								<td><select name="database_type" id="database_type" onchange="update_qs();">
									$db_m
									$db_p
									$db_s
								</select></td>
							</tr>
							<tr class="dbconf mysql pgsql">
								<th>Host:</th>
								<td><input type="text" name="database_host" size="40" value="localhost"></td>
							</tr>
							<tr class="dbconf mysql pgsql">
								<th>Username:</th>
								<td><input type="text" name="database_user" size="40"></td>
							</tr>
							<tr class="dbconf mysql pgsql">
								<th>Password:</th>
								<td><input type="password" name="database_password" size="40"></td>
							</tr>
							<tr class="dbconf mysql pgsql">
								<th>DB&nbsp;Name:</th>
								<td><input type="text" name="database_name" size="40" value="shimmie"></td>
							</tr>
							<tr><td colspan="2"><input type="submit" value="Go!"></td></tr>
						</table>
					</center>
					<script>
					$(function() {
						update_qs();
					});
					function update_qs() {
						$(".dbconf").hide();
						var seldb = $("#database_type").val() || "none";
						$("."+seldb).show();
					}
					</script>
				</form>

				<h3>Help</h3>

				<p class="dbconf mysql pgsql">
					Please make sure the database you have chosen exists and is empty.<br>
					The username provided must have access to create tables within the database.
				</p>
				<p class="dbconf sqlite">
					For SQLite the database name will be a filename on disk, relative to
					where shimmie was installed.
				</p>
				<p class="dbconf none">
					Drivers can generally be downloaded with your OS package manager;
					for Debian / Ubuntu you want php-pgsql, php-mysql, or php-sqlite.
				</p>
			</div>
		</div>
EOD;
} // }}}

/**
 * This is where the install really takes place.
 */
function install_process()
{ // {{{
    build_dirs();
    create_tables();
    insert_defaults();
    write_config();
} // }}}

function create_tables()
{ // {{{
    try {
        $db = new Database();

        if ($db->count_tables() > 0) {
            print <<<EOD
			<div id="installer">
				<h1>Shimmie Installer</h1>
				<h3>Warning: The Database schema is not empty!</h3>
				<div class="container">
					<p>Please ensure that the database you are installing Shimmie with is empty before continuing.</p>
					<p>Once you have emptied the database of any tables, please hit 'refresh' to continue.</p>
					<br/><br/>
				</div>
			</div>
EOD;
            exit(2);
        }

        $db->create_table("aliases", "
			oldtag VARCHAR(128) NOT NULL,
			newtag VARCHAR(128) NOT NULL,
			PRIMARY KEY (oldtag)
		");
        $db->execute("CREATE INDEX aliases_newtag_idx ON aliases(newtag)", []);

        $db->create_table("config", "
			name VARCHAR(128) NOT NULL,
			value TEXT,
			PRIMARY KEY (name)
		");
        $db->create_table("users", "
			id SCORE_AIPK,
			name VARCHAR(32) UNIQUE NOT NULL,
			pass VARCHAR(250),
			joindate SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			class VARCHAR(32) NOT NULL DEFAULT 'user',
			email VARCHAR(128)
		");
        $db->execute("CREATE INDEX users_name_idx ON users(name)", []);

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
			FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
		");
        $db->execute("CREATE INDEX images_owner_id_idx ON images(owner_id)", []);
        $db->execute("CREATE INDEX images_width_idx ON images(width)", []);
        $db->execute("CREATE INDEX images_height_idx ON images(height)", []);
        $db->execute("CREATE INDEX images_hash_idx ON images(hash)", []);

        $db->create_table("tags", "
			id SCORE_AIPK,
			tag VARCHAR(64) UNIQUE NOT NULL,
			count INTEGER NOT NULL DEFAULT 0
		");
        $db->execute("CREATE INDEX tags_tag_idx ON tags(tag)", []);

        $db->create_table("image_tags", "
			image_id INTEGER NOT NULL,
			tag_id INTEGER NOT NULL,
			UNIQUE(image_id, tag_id),
			FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
			FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
		");
        $db->execute("CREATE INDEX images_tags_image_id_idx ON image_tags(image_id)", []);
        $db->execute("CREATE INDEX images_tags_tag_id_idx ON image_tags(tag_id)", []);

        $db->execute("INSERT INTO config(name, value) VALUES('db_version', 11)");
        $db->commit();
    } catch (PDOException $e) {
        handle_db_errors(true, "An error occurred while trying to create the database tables necessary for Shimmie.", $e->getMessage(), 3);
    } catch (Exception $e) {
        handle_db_errors(false, "An unknown error occurred while trying to insert data into the database.", $e->getMessage(), 4);
    }
} // }}}

function insert_defaults()
{ // {{{
    try {
        $db = new Database();

        $db->execute("INSERT INTO users(name, pass, joindate, class) VALUES(:name, :pass, now(), :class)", ["name" => 'Anonymous', "pass" => null, "class" => 'anonymous']);
        $db->execute("INSERT INTO config(name, value) VALUES(:name, :value)", ["name" => 'anon_id', "value" => $db->get_last_insert_id('users_id_seq')]);

        if (check_im_version() > 0) {
            $db->execute("INSERT INTO config(name, value) VALUES(:name, :value)", ["name" => 'thumb_engine', "value" => 'convert']);
        }
        $db->commit();
    } catch (PDOException $e) {
        handle_db_errors(true, "An error occurred while trying to insert data into the database.", $e->getMessage(), 5);
    } catch (Exception $e) {
        handle_db_errors(false, "An unknown error occurred while trying to insert data into the database.", $e->getMessage(), 6);
    }
} // }}}

function build_dirs()
{ // {{{
    // *try* and make default dirs. Ignore any errors --
    // if something is amiss, we'll tell the user later
    if (!file_exists("data")) {
        @mkdir("data");
    }
    if (!is_writable("data")) {
        @chmod("data", 0755);
    }

    // Clear file status cache before checking again.
    clearstatcache();

    if (!file_exists("data") || !is_writable("data")) {
        print "
		<div id='installer'>
			<h1>Shimmie Installer</h1>
			<h3>Directory Permissions Error:</h3>
			<div class='container'>
				<p>Shimmie needs to have a 'data' folder in its directory, writable by the PHP user.</p>
				<p>If you see this error, if probably means the folder is owned by you, and it needs to be writable by the web server.</p>
				<p>PHP reports that it is currently running as user: ".$_ENV["USER"]." (". $_SERVER["USER"] .")</p>
				<p>Once you have created this folder and / or changed the ownership of the shimmie folder, hit 'refresh' to continue.</p>
				<br/><br/>
			</div>
		</div>
		";
        exit(7);
    }
} // }}}

function write_config()
{ // {{{
    $file_content = '<' . '?php' . "\n" .
            "define('DATABASE_DSN', '".DATABASE_DSN."');\n" .
            '?' . '>';

    if (!file_exists("data/config")) {
        mkdir("data/config", 0755, true);
    }

    if (file_put_contents("data/config/shimmie.conf.php", $file_content, LOCK_EX)) {
        header("Location: index.php");
        print <<<EOD
		<div id="installer">
			<h1>Shimmie Installer</h1>
			<h3>Things are OK \o/</h3>
			<div class="container">
				<p>If you aren't redirected, <a href="index.php">click here to Continue</a>.
			</div>
		</div>
EOD;
    } else {
        $h_file_content = htmlentities($file_content);
        print <<<EOD
		<div id="installer">
			<h1>Shimmie Installer</h1>
			<h3>File Permissions Error:</h3>
			<div class="container">
				The web server isn't allowed to write to the config file; please copy
				the text below, save it as 'data/config/shimmie.conf.php', and upload it into the shimmie
				folder manually. Make sure that when you save it, there is no whitespace
				before the "&lt;?php" or after the "?&gt;"
				
				<p><textarea cols="80" rows="2">$h_file_content</textarea>
				
				<p>Once done, <a href="index.php">click here to Continue</a>.
				<br/><br/>
			</div>
		</div>
EOD;
    }
    echo "\n";
} // }}}

function handle_db_errors(bool $isPDO, string $errorMessage1, string $errorMessage2, int $exitCode)
{
    $errorMessage1Extra = ($isPDO ? "Please check and ensure that the database configuration options are all correct." : "Please check the server log files for more information.");
    print <<<EOD
		<div id="installer">
			<h1>Shimmie Installer</h1>
			<h3>Unknown Error:</h3>
			<div class="container">
				<p>{$errorMessage1}</p>
				<p>{$errorMessage1Extra}</p>
				<p>{$errorMessage2}</p>
			</div>
		</div>
EOD;
    exit($exitCode);
}
?>
	</body>
</html>
