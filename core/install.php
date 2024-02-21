<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "core/urls.php";

/**
 * Shimmie Installer
 *
 * @package    Shimmie
 * @copyright  Copyright (c) 2007-2015, Shish et al.
 * @author     Shish [webmaster at shishnet.org], jgen [jeffgenovy at gmail.com]
 * @link       https://code.shishnet.org/shimmie2/
 * @license    https://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Initialise the database, check that folder
 * permissions are set properly.
 *
 * This file should be independent of the database
 * and other such things that aren't ready yet
 */

function install(): void
{
    date_default_timezone_set('UTC');

    if (is_readable("data/config/shimmie.conf.php")) {
        die_nicely(
            "Shimmie is already installed.",
            "data/config/shimmie.conf.php exists, how did you get here?"
        );
    }

    // Pull in necessary files
    require_once "vendor/autoload.php";
    global $_tracer;
    $_tracer = new \EventTracer();

    require_once "core/exceptions.php";
    require_once "core/cacheengine.php";
    require_once "core/dbengine.php";
    require_once "core/database.php";
    require_once "core/util.php";

    $dsn = get_dsn();
    if ($dsn) {
        do_install($dsn);
    } else {
        if (PHP_SAPI == 'cli') {
            print("INSTALL_DSN needs to be set for CLI installation\n");
            exit(1);
        } else {
            ask_questions();
        }
    }
}

function get_dsn(): ?string
{
    if (getenv("INSTALL_DSN")) {
        $dsn = getenv("INSTALL_DSN");
    } elseif (@$_POST["database_type"] == DatabaseDriverID::SQLITE->value) {
        /** @noinspection PhpUnhandledExceptionInspection */
        $id = bin2hex(random_bytes(5));
        $dsn = "sqlite:data/shimmie.{$id}.sqlite";
    } elseif (isset($_POST['database_type']) && isset($_POST['database_host']) && isset($_POST['database_user']) && isset($_POST['database_name'])) {
        $dsn = "{$_POST['database_type']}:user={$_POST['database_user']};password={$_POST['database_password']};host={$_POST['database_host']};dbname={$_POST['database_name']}";
    } else {
        $dsn = null;
    }
    return $dsn;
}

function do_install(string $dsn): void
{
    try {
        create_dirs();
        create_tables(new Database($dsn));
        write_config($dsn);
    } catch (InstallerException $e) {
        die_nicely($e->title, $e->body, $e->exit_code);
    }
}

function ask_questions(): void
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

    $drivers = \PDO::getAvailableDrivers();
    if (
        !in_array(DatabaseDriverID::MYSQL->value, $drivers) &&
        !in_array(DatabaseDriverID::PGSQL->value, $drivers) &&
        !in_array(DatabaseDriverID::SQLITE->value, $drivers)
    ) {
        $errors[] = "
			No database connection library could be found; shimmie needs
			PDO with either Postgres, MySQL, or SQLite drivers
		";
    }

    $db_s = in_array(DatabaseDriverID::SQLITE->value, $drivers) ? '<option value="'. DatabaseDriverID::SQLITE->value .'">SQLite</option>' : "";
    $db_m = in_array(DatabaseDriverID::MYSQL->value, $drivers) ? '<option value="'. DatabaseDriverID::MYSQL->value .'">MySQL</option>' : "";
    $db_p = in_array(DatabaseDriverID::PGSQL->value, $drivers) ? '<option value="'. DatabaseDriverID::PGSQL->value .'">PostgreSQL</option>' : "";

    $warn_msg = $warnings ? "<h3>Warnings</h3>".implode("\n<p>", $warnings) : "";
    $err_msg = $errors ? "<h3>Errors</h3>".implode("\n<p>", $errors) : "";

    $data_href = get_base_href();

    die_nicely(
        "Install Options",
        <<<EOD
    $warn_msg
    $err_msg

    <form action="$data_href/index.php" method="POST">
		<table class='form' style="margin: 1em auto;">
			<tr>
				<th>Type:</th>
				<td><select name="database_type" id="database_type" onchange="update_qs();">
                    $db_s
                    $db_m
					$db_p
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
        <script>
        document.addEventListener('DOMContentLoaded', update_qs);
        function q(n) {
            return document.querySelectorAll(n);
        }
        function update_qs() {
            q('.dbconf').forEach(el => el.style.display = 'none');
            let seldb = q("#database_type")[0].value || "none";
            q('.'+seldb).forEach(el => el.style.display = null);
        }
        </script>
    </form>

    <h3>Help</h3>
    <p class="dbconf mysql pgsql">
        Please make sure the database you have chosen exists and is empty.<br>
        The username provided must have access to create tables within the database.
    </p>
    <p class="dbconf sqlite">
        SQLite with default settings is fine for tens of users with thousands
        of images. For thousands of users or millions of images, postgres is
        recommended.
    </p>
    <p class="dbconf none">
        Drivers can generally be downloaded with your OS package manager;
        for Debian / Ubuntu you want php-pgsql, php-mysql, or php-sqlite.
    </p>
EOD
    );
}


function create_dirs(): void
{
    $data_exists = file_exists("data") || mkdir("data");
    $data_writable = $data_exists && (is_writable("data") || chmod("data", 0755));

    if (!$data_exists || !$data_writable) {
        throw new InstallerException(
            "Directory Permissions Error:",
            "<p>Shimmie needs to have a 'data' folder in its directory, writable by the PHP user.</p>
			<p>If you see this error, if probably means the folder is owned by you, and it needs to be writable by the web server.</p>
			<p>PHP reports that it is currently running as user: ".get_current_user()." (". getmyuid() .")</p>
			<p>Once you have created this folder and / or changed the ownership of the shimmie folder, hit 'refresh' to continue.</p>",
            7
        );
    }
}

function create_tables(Database $db): void
{
    try {
        if ($db->count_tables() > 0) {
            throw new InstallerException(
                "Warning: The Database schema is not empty!",
                "<p>Please ensure that the database you are installing Shimmie with is empty before continuing.</p>
				<p>Once you have emptied the database of any tables, please hit 'refresh' to continue.</p>",
                2
            );
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
			joindate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			class VARCHAR(32) NOT NULL DEFAULT 'user',
			email VARCHAR(128)
		");
        $db->execute("CREATE INDEX users_name_idx ON users(name)", []);

        $db->execute("INSERT INTO users(name, pass, joindate, class) VALUES(:name, :pass, now(), :class)", ["name" => 'Anonymous', "pass" => null, "class" => 'anonymous']);
        $db->execute("INSERT INTO config(name, value) VALUES(:name, :value)", ["name" => 'anon_id', "value" => $db->get_last_insert_id('users_id_seq')]);

        if (check_im_version() > 0) {
            $db->execute("INSERT INTO config(name, value) VALUES(:name, :value)", ["name" => 'thumb_engine', "value" => 'convert']);
        }

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
			posted TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			locked BOOLEAN NOT NULL DEFAULT FALSE,
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

        // mysql auto-commits when creating a table, so the transaction
        // is closed; other databases need to commit
        if ($db->is_transaction_open()) {
            $db->commit();
        }
        // Ensure that we end this code in a transaction (for testing)
        $db->begin_transaction();
    } catch (\PDOException $e) {
        throw new InstallerException(
            "PDO Error:",
            "<p>An error occurred while trying to create the database tables necessary for Shimmie.</p>
		    <p>Please check and ensure that the database configuration options are all correct.</p>
		    <p>{$e->getMessage()}</p>
		    ",
            3
        );
    }
}

function write_config(string $dsn): void
{
    $file_content = "<" . "?php\ndefine('DATABASE_DSN', '$dsn');\n";

    if (!file_exists("data/config")) {
        mkdir("data/config", 0755, true);
    }

    if (file_put_contents("data/config/shimmie.conf.php", $file_content, LOCK_EX)) {
        if (PHP_SAPI == 'cli') {
            print("Installation Successful\n");
            exit(0);
        } else {
            header("Location: index.php?flash=Installation%20complete");
            die_nicely(
                "Installation Successful",
                "<p>If you aren't redirected, <a href=\"index.php\">click here to Continue</a>."
            );
        }
    } else {
        $h_file_content = htmlentities($file_content);
        throw new InstallerException(
            "File Permissions Error:",
            "The web server isn't allowed to write to the config file; please copy
			the text below, save it as 'data/config/shimmie.conf.php', and upload it into the shimmie
			folder manually. Make sure that when you save it, there is no whitespace
			before the \"&lt;?php\".

			<p><textarea cols='80' rows='2'>$h_file_content</textarea>

			<p>Once done, <a href='index.php'>click here to Continue</a>.",
            0
        );
    }
}
