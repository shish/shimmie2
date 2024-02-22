<?php

declare(strict_types=1);

namespace Shimmie2;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Misc                                                                      *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

const DATA_DIR = "data";


function get_theme(): string
{
    global $config;
    $theme = $config->get_string(SetupConfig::THEME, "default");
    if (!file_exists("themes/$theme")) {
        $theme = "default";
    }
    return $theme;
}

function contact_link(?string $contact = null): ?string
{
    global $config;
    $text = $contact ?? $config->get_string('contact_link');
    if (is_null($text)) {
        return null;
    }

    if (
        str_starts_with($text, "http:") ||
        str_starts_with($text, "https:") ||
        str_starts_with($text, "mailto:")
    ) {
        return $text;
    }

    if (str_contains($text, "@")) {
        return "mailto:$text";
    }

    if (str_contains($text, "/")) {
        return "https://$text";
    }

    return $text;
}

/**
 * Check if HTTPS is enabled for the server.
 */
function is_https_enabled(): bool
{
    // check forwarded protocol
    if (is_trusted_proxy() && !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $_SERVER['HTTPS'] = 'on';
    }
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
    $default_limit = 8 * 1024 * 1024;	// 8 MB of memory is PHP's default.
    $shimmie_limit = $config->get_int(MediaConfig::MEM_LIMIT);

    if ($shimmie_limit < 3 * 1024 * 1024) {
        // we aren't going to fit, override
        $shimmie_limit = $default_limit;
    }

    /*
    Get PHP's configured memory limit.
    Note that this is set to -1 for NO memory limit.

    https://ca2.php.net/manual/en/ini.core.php#ini.memory-limit
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
    $convert_check = exec("convert --version");

    return (empty($convert_check) ? 0 : 1);
}

function is_trusted_proxy(): bool
{
    $ra = $_SERVER['REMOTE_ADDR'] ?? "0.0.0.0";
    if(!defined("TRUSTED_PROXIES")) {
        return false;
    }
    // @phpstan-ignore-next-line - TRUSTED_PROXIES is defined in config
    foreach(TRUSTED_PROXIES as $proxy) {
        if($ra === $proxy) { // check for "unix:" before checking IPs
            return true;
        }
        if(ip_in_range($ra, $proxy)) {
            return true;
        }
    }
    return false;
}

/**
 * Get real IP if behind a reverse proxy
 */
function get_real_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'];

    if($ip == "unix:") {
        $ip = "0.0.0.0";
    }

    if(is_trusted_proxy()) {
        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            if(filter_var_ex($ip, FILTER_VALIDATE_IP)) {
                $ip = $_SERVER['HTTP_X_REAL_IP'];
            }
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $last_ip = $ips[count($ips) - 1];
            if(filter_var_ex($last_ip, FILTER_VALIDATE_IP)) {
                $ip = $last_ip;
            }
        }
    }

    return $ip;
}

/**
 * Get the currently active IP, masked to make it not change when the last
 * octet or two change, for use in session cookies and such
 */
function get_session_ip(Config $config): string
{
    $mask = $config->get_string("session_hash_mask", "255.255.0.0");
    $addr = get_real_ip();
    $addr = \Safe\inet_ntop(inet_pton_ex($addr) & inet_pton_ex($mask));
    return $addr;
}


/**
 * A shorthand way to send a TextFormattingEvent and get the results.
 */
function format_text(string $string): string
{
    $event = send_event(new TextFormattingEvent($string));
    return $event->formatted;
}

/**
 * Take a map of string to string-or-array, return only the string-to-string subset
 *
 * @param array<string, string|string[]> $map
 * @return array<string, string>
 */
function only_strings(array $map): array
{
    $out = [];
    foreach ($map as $k => $v) {
        if (is_string($v)) {
            $out[$k] = $v;
        }
    }
    return $out;
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
function warehouse_path(string $base, string $hash, bool $create = true, int $splits = WH_SPLITS): string
{
    $dirs = [DATA_DIR, $base];
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
    if ($create && !file_exists(dirname($filename))) {
        mkdir(dirname($filename), 0755, true);
    }
    return $filename;
}

function load_balance_url(string $tmpl, string $hash, int $n = 0): string
{
    static $flexihashes = [];
    $matches = [];
    if (preg_match("/(.*){(.*)}(.*)/", $tmpl, $matches)) {
        $pre = $matches[1];
        $opts = $matches[2];
        $post = $matches[3];

        if (isset($flexihashes[$opts])) {
            $flexihash = $flexihashes[$opts];
        } else {
            $flexihash = new \Flexihash\Flexihash();
            foreach (explode(",", $opts) as $opt) {
                $parts = explode("=", $opt);
                $parts_count = count($parts);
                $opt_val = "";
                $opt_weight = 0;
                if ($parts_count === 2) {
                    $opt_val = $parts[0];
                    $opt_weight = (int)$parts[1];
                } elseif ($parts_count === 1) {
                    $opt_val = $parts[0];
                    $opt_weight = 1;
                }
                $flexihash->addTarget($opt_val, $opt_weight);
            }
            $flexihashes[$opts] = $flexihash;
        }

        // $choice = $flexihash->lookup($pre.$post);
        $choices = $flexihash->lookupList($hash, $n + 1);  // hash doesn't change
        $choice = $choices[$n];
        $tmpl = $pre . $choice . $post;
    }
    return $tmpl;
}

class FetchException extends \Exception
{
}

/**
 * @return array<string, string|string[]>
 */
function fetch_url(string $url, string $mfile): array
{
    global $config;

    if ($config->get_string(UploadConfig::TRANSLOAD_ENGINE) === "curl" && function_exists("curl_init")) {
        $ch = curl_init($url);
        assert($ch !== false);
        $fp = \Safe\fopen($mfile, "w");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        # curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Shimmie-".VERSION);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new FetchException("cURL failed: ".curl_error($ch));
        }
        if ($response === true) { // we use CURLOPT_RETURNTRANSFER, so this should never happen
            throw new FetchException("cURL failed successfully??");
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header_text = trim(substr($response, 0, $header_size));
        $headers = http_parse_headers(implode("\n", \Safe\preg_split('/\R/', $header_text)));
        $body = substr($response, $header_size);

        curl_close($ch);
        fwrite($fp, $body);
        fclose($fp);
    } elseif ($config->get_string(UploadConfig::TRANSLOAD_ENGINE) === "wget") {
        $s_url = escapeshellarg($url);
        $s_mfile = escapeshellarg($mfile);
        system("wget --no-check-certificate $s_url --output-document=$s_mfile");
        if(!file_exists($mfile)) {
            throw new FetchException("wget failed");
        }
        $headers = [];
    } elseif ($config->get_string(UploadConfig::TRANSLOAD_ENGINE) === "fopen") {
        $fp_in = @fopen($url, "r");
        $fp_out = fopen($mfile, "w");
        if (!$fp_in || !$fp_out) {
            throw new FetchException("fopen failed");
        }
        $length = 0;
        while (!feof($fp_in) && $length <= $config->get_int(UploadConfig::SIZE)) {
            $data = \Safe\fread($fp_in, 8192);
            $length += strlen($data);
            fwrite($fp_out, $data);
        }
        fclose($fp_in);
        fclose($fp_out);

        $headers = http_parse_headers(implode("\n", $http_response_header));
    } else {
        throw new FetchException("No transload engine configured");
    }

    if (filesize($mfile) == 0) {
        @unlink($mfile);
        throw new FetchException("No data found in $url -- perhaps the site has hotlink protection?");
    }

    return $headers;
}

/**
 * @return string[]
 */
function path_to_tags(string $path): array
{
    $matches = [];
    $tags = [];
    if (preg_match("/\d+ - (.+)\.([a-zA-Z0-9]+)/", basename($path), $matches)) {
        $tags = explode(" ", $matches[1]);
    }

    $path = str_replace("\\", "/", $path);
    $path = str_replace(";", ":", $path);
    $path = str_replace("__", " ", $path);
    $path = dirname($path);
    if ($path == "\\" || $path == "/" || $path == ".") {
        $path = "";
    }

    $category = "";
    foreach (explode("/", $path) as $dir) {
        $category_to_inherit = "";
        foreach (explode(" ", $dir) as $tag) {
            $tag = trim($tag);
            if ($tag == "") {
                continue;
            }
            if (substr_compare($tag, ":", -1) === 0) {
                // This indicates a tag that ends in a colon,
                // which is for inheriting to tags on the subfolder
                $category_to_inherit = $tag;
            } else {
                if ($category != "" && !str_contains($tag, ":")) {
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

    return $tags;
}

/**
 * @return string[]
 */
function get_dir_contents(string $dir): array
{
    assert(!empty($dir));

    if (!is_dir($dir)) {
        return [];
    }
    return array_diff(
        \Safe\scandir($dir),
        ['..', '.']
    );
}

function remove_empty_dirs(string $dir): bool
{
    $result = true;

    $items = get_dir_contents($dir);
    ;
    foreach ($items as $item) {
        $path = join_path($dir, $item);
        if (is_dir($path)) {
            $result = $result && remove_empty_dirs($path);
        } else {
            $result = false;
        }
    }
    if ($result === true) {
        $result = rmdir($dir);
    }
    return $result;
}

/**
 * @return string[]
 */
function get_files_recursively(string $dir): array
{
    $things = get_dir_contents($dir);

    $output = [];

    foreach ($things as $thing) {
        $path = join_path($dir, $thing);
        if (is_file($path)) {
            $output[] = $path;
        } else {
            $output = array_merge($output, get_files_recursively($path));
        }
    }

    return $output;
}

/**
 * Returns amount of files & total size of dir.
 *
 * @return array{"path": string, "total_files": int, "total_mb": string}
 */
function scan_dir(string $path): array
{
    $bytestotal = 0;
    $nbfiles = 0;

    $ite = new \RecursiveDirectoryIterator(
        $path,
        \FilesystemIterator::KEY_AS_PATHNAME |
        \FilesystemIterator::CURRENT_AS_FILEINFO |
        \FilesystemIterator::SKIP_DOTS
    );
    foreach (new \RecursiveIteratorIterator($ite) as $filename => $cur) {
        try {
            $filesize = $cur->getSize();
            $bytestotal += $filesize;
            $nbfiles++;
        } catch (\RuntimeException $e) {
            // This usually just means that the file got eaten by the import
            continue;
        }
    }

    $size_mb = $bytestotal / 1048576; // to mb
    $size_mb = number_format($size_mb, 2, '.', '');
    return ['path' => $path, 'total_files' => $nbfiles, 'total_mb' => $size_mb];
}


/**
 * because microtime() returns string|float, and we only ever want float
 */
function ftime(): float
{
    return (float)microtime(true);
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Debugging functions                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

$_shm_load_start = ftime();

/**
 * Collects some debug information (execution time, memory usage, queries, etc)
 * and formats it to stick in the footer of the page.
 */
function get_debug_info(): string
{
    $d = get_debug_info_arr();

    $debug = "<br>Took {$d['time']} seconds (db:{$d['dbtime']}) and {$d['mem_mb']}MB of RAM";
    $debug .= "; Used {$d['files']} files and {$d['query_count']} queries";
    $debug .= "; Sent {$d['event_count']} events";
    $debug .= "; {$d['cache_hits']} cache hits and {$d['cache_misses']} misses";
    $debug .= "; Shimmie version {$d['version']}";

    return $debug;
}

/**
 * Collects some debug information (execution time, memory usage, queries, etc)
 *
 * @return array<string, mixed>
 */
function get_debug_info_arr(): array
{
    global $cache, $config, $_shm_event_count, $database, $_shm_load_start;

    if ($config->get_string("commit_hash", "unknown") == "unknown") {
        $commit = "";
    } else {
        $commit = " (".$config->get_string("commit_hash").")";
    }

    return [
        "time" => round(ftime() - $_shm_load_start, 2),
        "dbtime" => round($database->dbtime, 2),
        "mem_mb" => round(((memory_get_peak_usage(true) + 512) / 1024) / 1024, 2),
        "files" => count(get_included_files()),
        "query_count" => $database->query_count,
        // "query_log" => $database->queries,
        "event_count" => $_shm_event_count,
        "cache_hits" => $cache->get("__etc_cache_hits"),
        "cache_misses" => $cache->get("__etc_cache_misses"),
        "version" => VERSION . $commit,
    ];
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Request initialisation stuff                                              *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * @param string[] $files
 */
function require_all(array $files): void
{
    foreach ($files as $filename) {
        require_once $filename;
    }
}

function _load_core_files(): void
{
    require_all(array_merge(
        zglob("core/*.php"),
        zglob("core/imageboard/*.php"),
        zglob("ext/*/info.php")
    ));
}

function _load_extension_files(): void
{
    ExtensionInfo::load_all_extension_info();
    Extension::determine_enabled_extensions();
    require_all(zglob("ext/{".Extension::get_enabled_extensions_as_string()."}/main.php"));
}

function _load_theme_files(): void
{
    $theme = get_theme();
    require_once('themes/'.$theme.'/page.class.php');
    require_once('themes/'.$theme.'/themelet.class.php');
    require_all(zglob("ext/{".Extension::get_enabled_extensions_as_string()."}/theme.php"));
    require_all(zglob('themes/'.$theme.'/{'.Extension::get_enabled_extensions_as_string().'}.theme.php'));
}

function _set_up_shimmie_environment(): void
{
    global $tracer_enabled;

    if (file_exists("images") && !file_exists("data/images")) {
        die_nicely("Upgrade error", "As of Shimmie 2.7 images and thumbs should be moved to data/images and data/thumbs");
    }

    if (TIMEZONE) {
        date_default_timezone_set(TIMEZONE);
    }

    if (DEBUG) {
        error_reporting(E_ALL);
    }

    // The trace system has a certain amount of memory consumption every time it is used,
    // so to prevent running out of memory during complex operations code that uses it should
    // check if tracer output is enabled before making use of it.
    // @phpstan-ignore-next-line - TRACE_FILE is defined in config
    $tracer_enabled = !is_null('TRACE_FILE');
}


/**
 * Used to display fatal errors to the web user.
 */
function _fatal_error(\Exception $e): void
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
            $i = $f['file'] ?? 'unknown file';
            $l = $f['line'] ?? -1;
            $a = implode(", ", array_map("Shimmie2\stringer", $f['args'] ?? []));
            print("$n: {$i}({$l}): {$c}{$t}{$f['function']}({$a})\n");
        }

        print("Message: $message\n");

        if (is_a($e, DatabaseException::class)) {
            print("Query:   {$e->query}\n");
            print("Args:    ".var_export($e->args, true)."\n");
        }

        print("Version: $version (on $phpver)\n");
    } else {
        $query = is_a($e, DatabaseException::class) ? $e->query : null;
        $code = is_a($e, SCoreException::class) ? $e->http_code : 500;

        $q = "";
        if(is_a($e, DatabaseException::class)) {
            $q .= "<p><b>Query:</b> " . html_escape($query);
            $q .= "<p><b>Args:</b> " . html_escape(var_export($e->args, true));
        }
        if ($code >= 500) {
            error_log("Shimmie Error: $message (Query: $query)\n{$e->getTraceAsString()}");
        }
        header("HTTP/1.0 $code Error");
        echo '
<!doctype html>
<html lang="en">
	<head>
		<title>Internal error - SCore-'.$version.'</title>
	</head>
	<body>
		<h1>Internal Error</h1>
		<p><b>Message:</b> '.html_escape($message).'
		'.$q.'
		<p><b>Version:</b> '.$version.' (on '.$phpver.')
        <p><b>Stack Trace:</b></p><pre>'.$e->getTraceAsString().'</pre>
	</body>
</html>
';
    }
}

function _get_user(): User
{
    global $config, $page;
    $my_user = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $parts = explode(" ", $_SERVER['HTTP_AUTHORIZATION'], 2);
        if (count($parts) == 2 && $parts[0] == "Bearer") {
            $parts = explode(":", $parts[1], 2);
            if (count($parts) == 2) {
                $my_user = User::by_session($parts[0], $parts[1]);
            }
        }
    }
    if (is_null($my_user) && $page->get_cookie("user") && $page->get_cookie("session")) {
        $my_user = User::by_session($page->get_cookie("user"), $page->get_cookie("session"));
    }
    if (is_null($my_user)) {
        $my_user = User::by_id($config->get_int("anon_id", 0));
    }
    assert(!is_null($my_user));

    return $my_user;
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
    $ban = $user->can(Permissions::BAN_IP) ? ", <a href='".make_link("ip_ban/list", "c_ip=$ip&c_reason=$u_reason&c_expires=$u_end", "create")."'>Ban</a>" : "";
    $ip = $user->can(Permissions::VIEW_IP) ? $ip.$ban : "";
    return $ip;
}

/**
 * Make a form tag with relevant auth token and stuff
 */
function make_form(string $target, bool $multipart = false, string $form_id = "", string $onsubmit = "", string $name = ""): string
{
    global $user;
    $at = $user->get_auth_token();

    $extra = empty($form_id) ? '' : 'id="'. $form_id .'"';
    if ($multipart) {
        $extra .= " enctype='multipart/form-data'";
    }
    if ($onsubmit) {
        $extra .= ' onsubmit="'.$onsubmit.'"';
    }
    if ($name) {
        $extra .= ' name="'.$name.'"';
    }
    return '<form action="'.$target.'" method="POST" '.$extra.'>'.
    '<input type="hidden" name="auth_token" value="'.$at.'">';
}

const BYTE_DENOMINATIONS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
function human_filesize(int $bytes, int $decimals = 2): string
{
    $factor = floor((strlen(strval($bytes)) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @BYTE_DENOMINATIONS[$factor];
}

/**
 * Generates a unique key for the website to prevent unauthorized access.
 */
function generate_key(int $length = 20): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters [rand(0, strlen($characters) - 1)];
    }

    return $randomString;
}

function shm_tempnam(string $prefix = ""): string
{
    if(!is_dir("data/temp")) {
        mkdir("data/temp");
    }
    $temp = \Safe\realpath("data/temp");
    return \Safe\tempnam($temp, $prefix);
}
