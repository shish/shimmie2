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

function get_theme_class(string $class): ?object
{
    $theme = ucfirst(get_theme());
    $options = [
        "\\Shimmie2\\$theme$class",
        "\\Shimmie2\\Custom$class",
        "\\Shimmie2\\$class",
    ];
    foreach ($options as $option) {
        if (class_exists($option)) {
            return new $option();
        }
    }
    return null;
}


function contact_link(?string $contact = null): ?string
{
    global $config;
    $text = $contact ?? $config->get_string(SetupConfig::CONTACT_LINK);
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

    if (str_contains($text, "/") && mb_substr($text, 0, 1) != "/") {
        return "https://$text";
    }

    return $text;
}

/**
 * Figure out PHP's internal memory limit
 */
function get_memory_limit(): int
{
    global $config;

    // thumbnail generation requires lots of memory
    $shimmie_limit = max(
        $config->get_int(MediaConfig::MEM_LIMIT),
        8 * 1024 * 1024 // don't go below 8MB
    );

    /*
    Get PHP's configured memory limit.
    Note that this is set to -1 for NO memory limit.

    https://ca2.php.net/manual/en/ini.core.php#ini.memory-limit
    */
    $php_limit = parse_shorthand_int(ini_get("memory_limit"));

    // If there's no system limit, or the system limit is higher
    // than what shimmie needs, use shimmie's limit as a soft max
    if ($php_limit === -1 || $php_limit === null || $php_limit >= $shimmie_limit) {
        return $shimmie_limit;
    }

    // If the system limit is less than Shimmie needs, try to
    // raise the limit
    if (ini_set("memory_limit", "$shimmie_limit") !== false) {
        $php_limit = parse_shorthand_int(ini_get("memory_limit")) ?? $shimmie_limit;
    }

    // Whether we managed to raise the limit, or we're stuck with
    // what we've got, return the current setting
    return $php_limit;
}

/**
 * Get the upload limits for Shimmie
 *
 * files / filesize / post are PHP system limits
 * shm_files / shm_filesize / shm_post are Shimmie limits
 *
 * @return array{"files": int|null, "filesize": int|null, "post": int|null, "shm_files": int, "shm_filesize": int, "shm_post": int}
 */
function get_upload_limits(): array
{
    global $config;

    $ini_files = ini_get('max_file_uploads');
    $ini_filesize = ini_get('upload_max_filesize');
    $ini_post = ini_get('post_max_size');

    $sys_files = empty($ini_files) ? null : parse_shorthand_int($ini_files);
    $sys_filesize = empty($ini_filesize) ? null : parse_shorthand_int($ini_filesize);
    $sys_post = empty($ini_post) ? null : parse_shorthand_int($ini_post);

    $conf_files = $config->get_int(UploadConfig::COUNT);
    $conf_filesize = $config->get_int(UploadConfig::SIZE);
    $conf_post = ($conf_files ?? 0) * ($conf_filesize ?? 0);

    $limits = [
        'files' => $sys_files,
        'filesize' => $sys_filesize,
        'post' => $sys_post,
        'shm_files' => (int)min($conf_files ?? PHP_INT_MAX, $sys_files ?? PHP_INT_MAX),
        'shm_filesize' => (int)min($conf_filesize ?? PHP_INT_MAX, $sys_filesize ?? PHP_INT_MAX),
        'shm_post' => (int)min($conf_post, $sys_post ?? PHP_INT_MAX),
    ];

    return $limits;
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
 * because microtime() returns string|float, and we only ever want float
 */
function ftime(): float
{
    return (float)microtime(true);
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Debugging functions                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Collects some debug information (execution time, memory usage, queries, etc)
 * and formats it to stick in the footer of the page.
 */
function get_debug_info(): string
{
    $d = get_debug_info_arr();

    $debug = "Took {$d['time']} seconds (db:{$d['dbtime']}) and {$d['mem_mb']}MB of RAM";
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
    global $cache, $config, $_shm_event_bus, $database, $_shm_load_start;

    return [
        "time" => round(ftime() - $_shm_load_start, 2),
        "dbtime" => round($database->dbtime, 2),
        "mem_mb" => round(((memory_get_peak_usage(true) + 512) / 1024) / 1024, 2),
        "files" => count(get_included_files()),
        "query_count" => $database->query_count,
        // "query_log" => $database->queries,
        "event_count" => $_shm_event_bus->event_count,
        "cache_hits" => $cache->get("__etc_cache_hits"),
        "cache_misses" => $cache->get("__etc_cache_misses"),
        "version" => SysConfig::getVersion(),
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

function _load_ext_files(): void
{
    global $_tracer;
    $_tracer->begin("Load Ext Files");
    require_all(array_merge(
        Filesystem::zglob("ext/*/info.php"),
        Filesystem::zglob("ext/*/config.php"),
        Filesystem::zglob("ext/*/permissions.php"),
        Filesystem::zglob("ext/*/theme.php"),
        Filesystem::zglob("ext/*/main.php"),
    ));
    $_tracer->end();
}

function _load_theme_files(): void
{
    global $_tracer;
    $_tracer->begin("Load Theme Files");
    $theme = get_theme();
    require_once('themes/'.$theme.'/page.class.php');
    require_all(Filesystem::zglob('themes/'.$theme.'/*.theme.php'));
    $_tracer->end();
}

function _set_up_shimmie_environment(): void
{
    global $tracer_enabled;

    if (file_exists("images") && !file_exists("data/images")) {
        die_nicely("Upgrade error", "As of Shimmie 2.7 images and thumbs should be moved to data/images and data/thumbs");
    }

    if (SysConfig::getTimezone()) {
        date_default_timezone_set(SysConfig::getTimezone());
    }

    if (SysConfig::getDebug()) {
        error_reporting(E_ALL);
    }

    // The trace system has a certain amount of memory consumption every time it is used,
    // so to prevent running out of memory during complex operations code that uses it should
    // check if tracer output is enabled before making use of it.
    $tracer_enabled = !is_null(SysConfig::getTraceFile());
}

/**
 * Used to display fatal errors to the web user.
 */
function _fatal_error(\Exception $e): void
{
    $version = SysConfig::getVersion();
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
            $a = implode(", ", array_map(stringer(...), $f['args'] ?? []));
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
        if (is_a($e, DatabaseException::class)) {
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
        <p><b>Stack Trace:</b></p><pre><code>'.$e->getTraceAsString().'</code></pre>
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
        $my_user = User::by_id($config->get_int(UserAccountsConfig::ANON_ID, 0));
    }

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
    $ban = $user->can(IPBanPermission::BAN_IP) ? ", <a href='".make_link("ip_ban/list", "c_ip=$ip&c_reason=$u_reason&c_expires=$u_end", "create")."'>Ban</a>" : "";
    $ip = $user->can(IPBanPermission::VIEW_IP) ? $ip.$ban : "";
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
    if (!is_dir("data/temp")) {
        mkdir("data/temp");
    }
    $temp = \Safe\realpath("data/temp");
    return \Safe\tempnam($temp, $prefix);
}
