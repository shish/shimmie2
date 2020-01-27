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
define("DATABASE_TIMEOUT", 10000);

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
require_once "vendor/autoload.php";
$_tracer = new EventTracer();

require_once "core/exceptions.php";
require_once "core/cacheengine.php";
require_once "core/dbengine.php";
require_once "core/database.php";
require_once "core/util.php";

if (is_readable("data/config/shimmie.conf.php")) {
    die("Shimmie is already installed.");
}

do_install();

// TODO: Can some of these be pushed into "core/???.inc.php" ?

function do_install()
{
    if (file_exists("data/config/auto_install.conf.php")) {
        require_once "data/config/auto_install.conf.php";
    } elseif (@$_POST["database_type"] == DatabaseDriver::SQLITE) {
        $id = bin2hex(random_bytes(5));
        define('DATABASE_DSN', "sqlite:data/shimmie.{$id}.sqlite");
    } elseif (isset($_POST['database_type']) && isset($_POST['database_host']) && isset($_POST['database_user']) && isset($_POST['database_name'])) {
        define('DATABASE_DSN', "{$_POST['database_type']}:user={$_POST['database_user']};password={$_POST['database_password']};host={$_POST['database_host']};dbname={$_POST['database_name']}");
    } else {
        ask_questions();
        return;
    }

    define("CACHE_DSN", null);
    try {
        create_dirs();
        create_tables(new Database(DATABASE_DSN));
        write_config();
    } catch (InstallerException $e) {
        print <<<EOD
		<div id="installer">
			<h1>Shimmie Installer</h1>
			<h3>{$e->title}</h3>
			<div class="container">
				{$e->body}
				<br/><br/>
			</div>
		</div>
EOD;
        exit($e->code);
    }
}

function ask_questions()
{
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
        !in_array(DatabaseDriver::MYSQL, $drivers) &&
        !in_array(DatabaseDriver::PGSQL, $drivers) &&
        !in_array(DatabaseDriver::SQLITE, $drivers)
    ) {
        $errors[] = "
			No database connection library could be found; shimmie needs
			PDO with either Postgres, MySQL, or SQLite drivers
		";
    }

    $db_m = in_array(DatabaseDriver::MYSQL, $drivers)  ? '<option value="'. DatabaseDriver::MYSQL .'">MySQL</option>' : "";
    $db_p = in_array(DatabaseDriver::PGSQL, $drivers)  ? '<option value="'. DatabaseDriver::PGSQL .'">PostgreSQL</option>' : "";
    $db_s = in_array(DatabaseDriver::SQLITE, $drivers) ? '<option value="'. DatabaseDriver::SQLITE .'">SQLite</option>' : "";

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
}
?>
	</body>
</html>
