<?php declare(strict_types=1);
use function MicroHTML\emptyHTML;
use function MicroHTML\rawHTML;
use function MicroHTML\FORM;
use function MicroHTML\INPUT;
use function MicroHTML\DIV;
use function MicroHTML\PRE;
use function MicroHTML\P;
use function MicroHTML\TABLE;
use function MicroHTML\THEAD;
use function MicroHTML\TFOOT;
use function MicroHTML\TR;
use function MicroHTML\TH;
use function MicroHTML\TD;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Misc                                                                      *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

const DATA_DIR = "data";


function mtimefile(string $file): string
{
    $data_href = get_base_href();
    $mtime = filemtime($file);
    return "$data_href/$file?$mtime";
}

function get_theme(): string
{
    global $config;
    $theme = $config->get_string(SetupConfig::THEME, "default");
    if (!file_exists("themes/$theme")) {
        $theme = "default";
    }
    return $theme;
}

function contact_link(): ?string
{
    global $config;
    $text = $config->get_string('contact_link');
    if (is_null($text)) {
        return null;
    }

    if (
        startsWith($text, "http:") ||
        startsWith($text, "https:") ||
        startsWith($text, "mailto:")
    ) {
        return $text;
    }

    if (strpos($text, "@")) {
        return "mailto:$text";
    }

    if (strpos($text, "/")) {
        return "http://$text";
    }

    return $text;
}

/**
 * Check if HTTPS is enabled for the server.
 */
function is_https_enabled(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
}

/**
 * Compare two Block objects, used to sort them before being displayed
 */
function blockcmp(Block $a, Block $b): int
{
    if ($a->position == $b->position) {
        return 0;
    } else {
        return ($a->position > $b->position) ? 1 : -1;
    }
}

/**
 * Figure out PHP's internal memory limit
 */
function get_memory_limit(): int
{
    global $config;

    // thumbnail generation requires lots of memory
    $default_limit = 8*1024*1024;	// 8 MB of memory is PHP's default.
    $shimmie_limit = $config->get_int(MediaConfig::MEM_LIMIT);

    if ($shimmie_limit < 3*1024*1024) {
        // we aren't going to fit, override
        $shimmie_limit = $default_limit;
    }

    /*
    Get PHP's configured memory limit.
    Note that this is set to -1 for NO memory limit.

    http://ca2.php.net/manual/en/ini.core.php#ini.memory-limit
    */
    $memory = parse_shorthand_int(ini_get("memory_limit"));

    if ($memory == -1) {
        // No memory limit.
        // Return the larger of the set limits.
        return max($shimmie_limit, $default_limit);
    } else {
        // PHP has a memory limit set.
        if ($shimmie_limit > $memory) {
            // Shimmie wants more memory than what PHP is currently set for.

            // Attempt to set PHP's memory limit.
            if (ini_set("memory_limit", "$shimmie_limit") === false) {
                /*  We can't change PHP's limit, oh well, return whatever its currently set to */
                return $memory;
            }
            $memory = parse_shorthand_int(ini_get("memory_limit"));
        }

        // PHP's memory limit is more than Shimmie needs.
        return $memory; // return the current setting
    }
}

/**
 * Check if PHP has the GD library installed
 */
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

/**
 * Check whether ImageMagick's `convert` command
 * is installed and working
 */
function check_im_version(): int
{
    $convert_check = exec("convert");

    return (empty($convert_check) ? 0 : 1);
}

/**
 * Get the currently active IP, masked to make it not change when the last
 * octet or two change, for use in session cookies and such
 */
function get_session_ip(Config $config): string
{
    $mask = $config->get_string("session_hash_mask", "255.255.0.0");
    $addr = $_SERVER['REMOTE_ADDR'];
    $addr = inet_ntop(inet_pton($addr) & inet_pton($mask));
    return $addr;
}


/**
 * A shorthand way to send a TextFormattingEvent and get the results.
 */
function format_text(string $string): string
{
    $tfe = send_event(new TextFormattingEvent($string));
    return $tfe->formatted;
}

/**
 * Generates the path to a file under the data folder based on the file's hash.
 * This process creates subfolders based on octet pairs from the file's hash.
 * The calculated folder follows this pattern data/$base/octet_pairs/$hash
 * @param string $base
 * @param string $hash
 * @param bool $create
 * @param int $splits The number of octet pairs to split the hash into. Caps out at strlen($hash)/2.
 * @return string
 */
function warehouse_path(string $base, string $hash, bool $create=true, int $splits = WH_SPLITS): string
{
    $dirs =[DATA_DIR, $base];
    $splits = min($splits, strlen($hash) / 2);
    for ($i = 0; $i < $splits; $i++) {
        $dirs[] = substr($hash, $i * 2, 2);
    }
    $dirs[] = $hash;

    $pa = join_path(...$dirs);

    if ($create && !file_exists(dirname($pa))) {
        mkdir(dirname($pa), 0755, true);
    }
    return $pa;
}

/**
 * Determines the path to the specified file in the data folder.
 */
function data_path(string $filename, bool $create = true): string
{
    $filename = join_path("data", $filename);
    if ($create&&!file_exists(dirname($filename))) {
        mkdir(dirname($filename), 0755, true);
    }
    return $filename;
}

function transload(string $url, string $mfile): ?array
{
    global $config;

    if ($config->get_string("transload_engine") === "curl" && function_exists("curl_init")) {
        $ch = curl_init($url);
        $fp = fopen($mfile, "w");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Shimmie-".VERSION);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);
        if ($response === false) {
            log_warning("core-util", "Failed to transload $url");
            throw new SCoreException("Failed to fetch $url");
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = http_parse_headers(implode("\n", preg_split('/\R/', rtrim(substr($response, 0, $header_size)))));
        $body = substr($response, $header_size);

        curl_close($ch);
        fwrite($fp, $body);
        fclose($fp);

        return $headers;
    }

    if ($config->get_string("transload_engine") === "wget") {
        $s_url = escapeshellarg($url);
        $s_mfile = escapeshellarg($mfile);
        system("wget --no-check-certificate $s_url --output-document=$s_mfile");

        return file_exists($mfile) ? ["ok"=>"true"] : null;
    }

    if ($config->get_string("transload_engine") === "fopen") {
        $fp_in = @fopen($url, "r");
        $fp_out = fopen($mfile, "w");
        if (!$fp_in || !$fp_out) {
            return null;
        }
        $length = 0;
        while (!feof($fp_in) && $length <= $config->get_int('upload_size')) {
            $data = fread($fp_in, 8192);
            $length += strlen($data);
            fwrite($fp_out, $data);
        }
        fclose($fp_in);
        fclose($fp_out);

        $headers = http_parse_headers(implode("\n", $http_response_header));

        return $headers;
    }

    return null;
}

/**
 * Get the active contents of a .php file
 */
function manual_include(string $fname): ?string
{
    static $included = [];

    if (!file_exists($fname)) {
        return null;
    }

    if (in_array($fname, $included)) {
        return null;
    }

    $included[] = $fname;

    print "$fname\n";

    $text = file_get_contents($fname);

    // we want one continuous file
    $text = str_replace('<'.'?php', '', $text);
    $text = str_replace('?'.'>', '', $text);

    // most requires are built-in, but we want /lib separately
    $text = str_replace('require_', '// require_', $text);
    $text = str_replace('// require_once "lib', 'require_once "lib', $text);

    // @include_once is used for user-creatable config files
    $text = preg_replace('/@include_once "(.*)";/e', "manual_include('$1')", $text);

    return $text;
}


function path_to_tags(string $path): string
{
    $matches = [];
    $tags = [];
    if (preg_match("/\d+ - (.+)\.([a-zA-Z0-9]+)/", basename($path), $matches)) {
        $tags = explode(" ", $matches[1]);
    }

    $path = dirname($path);
    $path = str_replace(";", ":", $path);
    $path = str_replace("__", " ", $path);


    $category = "";
    foreach (explode("/", $path) as $dir) {
        $category_to_inherit = "";
        foreach (explode(" ", $dir) as $tag) {
            $tag = trim($tag);
            if ($tag=="") {
                continue;
            }
            if (substr_compare($tag, ":", -1) === 0) {
                // This indicates a tag that ends in a colon,
                // which is for inheriting to tags on the subfolder
                $category_to_inherit = $tag;
            } else {
                if ($category!=""&&strpos($tag, ":") === false) {
                    // This indicates that category inheritance is active,
                    // and we've encountered a tag that does not specify a category.
                    // So we attach the inherited category to the tag.
                    $tag = $category.$tag;
                }
                $tags[] = $tag;
            }
        }
        // Category inheritance only works on the immediate subfolder,
        // so we hold a category until the next iteration, and then set
        // it back to an empty string after that iteration
        $category = $category_to_inherit;
    }

    return implode(" ", $tags);
}


function join_url(string $base, string ...$paths)
{
    $output = $base;
    foreach ($paths as $path) {
        $output = rtrim($output, "/");
        $path = ltrim($path, "/");
        $output .= "/".$path;
    }
    return $output;
}

function get_dir_contents(string $dir): array
{
    assert(!empty($dir));

    if (!is_dir($dir)) {
        return [];
    }
    return array_diff(
        scandir(
            $dir
        ),
        ['..', '.']
    );
}

/**
 * Returns amount of files & total size of dir.
 */
function scan_dir(string $path): array
{
    $bytestotal = 0;
    $nbfiles = 0;

    $ite = new RecursiveDirectoryIterator(
        $path,
        FilesystemIterator::KEY_AS_PATHNAME |
        FilesystemIterator::CURRENT_AS_FILEINFO |
        FilesystemIterator::SKIP_DOTS
    );
    foreach (new RecursiveIteratorIterator($ite) as $filename => $cur) {
        try {
            $filesize = $cur->getSize();
            $bytestotal += $filesize;
            $nbfiles++;
        } catch (RuntimeException $e) {
            // This usually just means that the file got eaten by the import
            continue;
        }
    }

    $size_mb = $bytestotal / 1048576; // to mb
    $size_mb = number_format($size_mb, 2, '.', '');
    return ['path' => $path, 'total_files' => $nbfiles, 'total_mb' => $size_mb];
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Debugging functions                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

// SHIT by default this returns the time as a string. And it's not even a
// string representation of a number, it's two numbers separated by a space.
// What the fuck were the PHP developers smoking.
$_shm_load_start = microtime(true);

/**
 * Collects some debug information (execution time, memory usage, queries, etc)
 * and formats it to stick in the footer of the page.
 */
function get_debug_info(): string
{
    global $cache, $config, $_shm_event_count, $database, $_shm_load_start;

    $i_mem = sprintf("%5.2f", ((memory_get_peak_usage(true)+512)/1024)/1024);

    if ($config->get_string("commit_hash", "unknown") == "unknown") {
        $commit = "";
    } else {
        $commit = " (".$config->get_string("commit_hash").")";
    }
    $time = sprintf("%.2f", microtime(true) - $_shm_load_start);
    $dbtime = sprintf("%.2f", $database->dbtime);
    $i_files = count(get_included_files());
    $hits = $cache->get_hits();
    $miss = $cache->get_misses();

    $debug = "<br>Took $time seconds (db:$dbtime) and {$i_mem}MB of RAM";
    $debug .= "; Used $i_files files and {$database->query_count} queries";
    $debug .= "; Sent $_shm_event_count events";
    $debug .= "; $hits cache hits and $miss misses";
    $debug .= "; Shimmie version ". VERSION . $commit;

    return $debug;
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Request initialisation stuff                                              *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/** @privatesection */

function require_all(array $files): void {
    foreach ($files as $filename) {
        if (basename($filename)[0] != "_") {
            require_once $filename;
        }
    }
}

function _load_core_files() {
    require_all(array_merge(
        zglob("core/*.php"),
        zglob("core/imageboard/*.php"),
        zglob("ext/*/info.php")
    ));
}

function _load_theme_files() {
    require_all(_get_themelet_files(get_theme()));
}

function _sanitise_environment(): void
{
    global $_tracer, $tracer_enabled;

    $min_php = "7.3";
    if (version_compare(phpversion(), $min_php, ">=") === false) {
        print "
Shimmie does not support versions of PHP lower than $min_php
(PHP reports that it is version ".phpversion().").
If your web host is running an older version, they are dangerously out of
date and you should plan on moving elsewhere.
";
        exit;
    }

    if (file_exists("images") && !file_exists("data/images")) {
        die("As of Shimmie 2.7 images and thumbs should be moved to data/images and data/thumbs");
    }

    if (TIMEZONE) {
        date_default_timezone_set(TIMEZONE);
    }

    # ini_set('zend.assertions', '1');  // generate assertions
    ini_set('assert.exception', '1');  // throw exceptions when failed
    if (DEBUG) {
        error_reporting(E_ALL);
    }

    // The trace system has a certain amount of memory consumption every time it is used,
    // so to prevent running out of memory during complex operations code that uses it should
    // check if tracer output is enabled before making use of it.
    $tracer_enabled = constant('TRACE_FILE')!==null;
    $_tracer = new EventTracer();

    if (COVERAGE) {
        _start_coverage();
        register_shutdown_function("_end_coverage");
    }

    ob_start();

    if (PHP_SAPI === 'cli' || PHP_SAPI == 'phpdbg') {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            die("CLI with remote addr? Confused, not taking the risk.");
        }
        $_SERVER['REMOTE_ADDR'] = "0.0.0.0";
        $_SERVER['HTTP_HOST'] = "<cli command>";
    }
}


function _get_themelet_files(string $_theme): array
{
    $base_themelets = [];
    $base_themelets[] = 'themes/'.$_theme.'/page.class.php';
    $base_themelets[] = 'themes/'.$_theme.'/layout.class.php';
    $base_themelets[] = 'themes/'.$_theme.'/themelet.class.php';

    $ext_themelets = zglob("ext/{".Extension::get_enabled_extensions_as_string()."}/theme.php");
    $custom_themelets = zglob('themes/'.$_theme.'/{'.Extension::get_enabled_extensions_as_string().'}.theme.php');

    return array_merge($base_themelets, $ext_themelets, $custom_themelets);
}


/**
 * Used to display fatal errors to the web user.
 */
function _fatal_error(Exception $e): void
{
    $version = VERSION;
    $message = $e->getMessage();
    $phpver = phpversion();

    //$hash = exec("git rev-parse HEAD");
    //$h_hash = $hash ? "<p><b>Hash:</b> $hash" : "";
    //'.$h_hash.'

    if (PHP_SAPI === 'cli' || PHP_SAPI == 'phpdbg') {
        print("Trace: ");
        $t = array_reverse($e->getTrace());
        foreach ($t as $n => $f) {
            $c = $f['class'] ?? '';
            $t = $f['type'] ?? '';
            $a = implode(", ", array_map("stringer", $f['args']));
            print("$n: {$f['file']}({$f['line']}): {$c}{$t}{$f['function']}({$a})\n");
        }

        print("Message: $message\n");

        if (isset($e->query)) {
            print("Query:   {$e->query}\n");
        }

        print("Version: $version (on $phpver)\n");
    } else {
        $q = (!isset($e->query) || is_null($e->query)) ? "" : "<p><b>Query:</b> " . html_escape($e->query);
        header("HTTP/1.0 500 Internal Error");
        echo '
<html>
	<head>
		<title>Internal error - SCore-'.$version.'</title>
	</head>
	<body>
		<h1>Internal Error</h1>
		<p><b>Message:</b> '.html_escape($message).'
		'.$q.'
		<p><b>Version:</b> '.$version.' (on '.$phpver.')
	</body>
</html>
';
    }
}

/**
 * Turn ^^ into ^ and ^s into /
 *
 * Necessary because various servers and various clients
 * think that / is special...
 */
function _decaret(string $str): string
{
    $out = "";
    $length = strlen($str);
    for ($i=0; $i<$length; $i++) {
        if ($str[$i] == "^") {
            $i++;
            if ($str[$i] == "^") {
                $out .= "^";
            }
            if ($str[$i] == "s") {
                $out .= "/";
            }
            if ($str[$i] == "b") {
                $out .= "\\";
            }
        } else {
            $out .= $str[$i];
        }
    }
    return $out;
}

function _get_user(): User
{
    global $config, $page;
    $my_user = null;
    if ($page->get_cookie("user") && $page->get_cookie("session")) {
        $tmp_user = User::by_session($page->get_cookie("user"), $page->get_cookie("session"));
        if (!is_null($tmp_user)) {
            $my_user = $tmp_user;
        }
    }
    if (is_null($my_user)) {
        $my_user = User::by_id($config->get_int("anon_id", 0));
    }
    assert(!is_null($my_user));

    return $my_user;
}

function _get_query(): string
{
    return (@$_POST["q"]?:@$_GET["q"])?:"/";
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Things used in the installer + unit tests                                 *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function create_dirs()
{
    $data_exists = file_exists("data") || mkdir("data");
    $data_writable = is_writable("data") || chmod("data", 0755);

    if (!$data_exists || !$data_writable) {
        throw new InstallerException(
            "Directory Permissions Error:",
            "<p>Shimmie needs to have a 'data' folder in its directory, writable by the PHP user.</p>
			<p>If you see this error, if probably means the folder is owned by you, and it needs to be writable by the web server.</p>
			<p>PHP reports that it is currently running as user: ".$_ENV["USER"]." (". $_SERVER["USER"] .")</p>
			<p>Once you have created this folder and / or changed the ownership of the shimmie folder, hit 'refresh' to continue.</p>",
            7
        );
    }
}

function create_tables(Database $db)
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
        throw new InstallerException(
            "PDO Error:",
            "<p>An error occurred while trying to create the database tables necessary for Shimmie.</p>
		    <p>Please check and ensure that the database configuration options are all correct.</p>
		    <p>{$e->getMessage()}</p>",
            3
        );
    } catch (Exception $e) {
        throw new InstallerException(
            "Unknown Error:",
            "<p>An unknown error occurred while trying to insert data into the database.</p>
		    <p>Please check the server log files for more information.</p>
		    <p>{$e->getMessage()}</p>",
            4
        );
    }
}

function write_config()
{
    $file_content = "<" . "?php\ndefine('DATABASE_DSN', '".DATABASE_DSN."');\n";

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
        throw new InstallerException(
            "File Permissions Error:",
            "The web server isn't allowed to write to the config file; please copy
			the text below, save it as 'data/config/shimmie.conf.php', and upload it into the shimmie
			folder manually. Make sure that when you save it, there is no whitespace
			before the \"&lt;?php\" or after the \"?&gt;\"

			<p><textarea cols='80' rows='2'>$h_file_content</textarea>

			<p>Once done, <a href='index.php'>click here to Continue</a>.",
            0
        );
    }
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Code coverage                                                             *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function _start_coverage(): void
{
    if (function_exists("xdebug_start_code_coverage")) {
        #xdebug_start_code_coverage(XDEBUG_CC_UNUSED|XDEBUG_CC_DEAD_CODE);
        xdebug_start_code_coverage(XDEBUG_CC_UNUSED);
    }
}

function _end_coverage(): void
{
    if (function_exists("xdebug_get_code_coverage")) {
        // Absolute path is necessary because working directory
        // inside register_shutdown_function is unpredictable.
        $absolute_path = dirname(dirname(__FILE__)) . "/data/coverage";
        if (!file_exists($absolute_path)) {
            mkdir($absolute_path);
        }
        $n = 0;
        $t = time();
        while (file_exists("$absolute_path/$t.$n.log")) {
            $n++;
        }
        file_put_contents("$absolute_path/$t.$n.log", gzdeflate(serialize(xdebug_get_code_coverage())));
    }
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* HTML Generation                                                           *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Give a HTML string which shows an IP (if the user is allowed to see IPs),
 * and a link to ban that IP (if the user is allowed to ban IPs)
 *
 * FIXME: also check that IP ban ext is installed
 */
function show_ip(string $ip, string $ban_reason): string
{
    global $user;
    $u_reason = url_escape($ban_reason);
    $u_end = url_escape("+1 week");
    $ban = $user->can(Permissions::BAN_IP) ? ", <a href='".make_link("ip_ban/list", "c_ip=$ip&c_reason=$u_reason&c_expires=$u_end#create")."'>Ban</a>" : "";
    $ip = $user->can(Permissions::VIEW_IP) ? $ip.$ban : "";
    return $ip;
}

/**
 * Make a form tag with relevant auth token and stuff
 */
function make_form(string $target, string $method="POST", bool $multipart=false, string $form_id="", string $onsubmit=""): string
{
    global $user;
    if ($method == "GET") {
        $link = html_escape($target);
        $target = make_link($target);
        $extra_inputs = "<input type='hidden' name='q' value='$link'>";
    } else {
        $extra_inputs = $user->get_auth_html();
    }

    $extra = empty($form_id) ? '' : 'id="'. $form_id .'"';
    if ($multipart) {
        $extra .= " enctype='multipart/form-data'";
    }
    if ($onsubmit) {
        $extra .= ' onsubmit="'.$onsubmit.'"';
    }
    return '<form action="'.$target.'" method="'.$method.'" '.$extra.'>'.$extra_inputs;
}

function SHM_FORM(string $target, string $method="POST", bool $multipart=false, string $form_id="", string $onsubmit="")
{
    global $user;

    $attrs = [
        "action"=>$target,
        "method"=>$method
    ];

    if ($form_id) {
        $attrs["id"] = $form_id;
    }
    if ($multipart) {
        $attrs["enctype"] = 'multipart/form-data';
    }
    if ($onsubmit) {
        $attrs["onsubmit"] = $onsubmit;
    }
    return FORM(
        $attrs,
        INPUT(["type"=>"hidden", "name"=>"q", "value"=>$target]),
        $method == "GET" ? "" : rawHTML($user->get_auth_html())
    );
}

function SHM_SIMPLE_FORM($target, ...$children)
{
    $form = SHM_FORM($target);
    $form->appendChild(emptyHTML(...$children));
    return $form;
}

function SHM_SUBMIT(string $text)
{
    return INPUT(["type"=>"submit", "value"=>$text]);
}

function SHM_COMMAND_EXAMPLE(string $ex, string $desc)
{
    return DIV(
        ["class"=>"command_example"],
        PRE($ex),
        P($desc)
    );
}

function SHM_USER_FORM(User $duser, string $target, string $title, $body, $foot)
{
    if (is_string($foot)) {
        $foot = TFOOT(TR(TD(["colspan"=>"2"], INPUT(["type"=>"submit", "value"=>$foot]))));
    }
    return SHM_SIMPLE_FORM(
        make_link($target),
        P(
            INPUT(["type"=>'hidden', "name"=>'id', "value"=>$duser->id]),
            TABLE(
                ["class"=>"form"],
                THEAD(TR(TH(["colspan"=>"2"], $title))),
                $body,
                $foot
            )
        )
    );
}
