<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B, BODY, CODE, H1, HEAD, HTML, P, PRE, TITLE, emptyHTML};

use MicroHTML\HTMLElement;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Misc                                                                      *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function get_theme(): string
{
    $theme = Ctx::$config->get(SetupConfig::THEME);
    if (!file_exists("themes/$theme")) {
        $theme = "default";
    }
    return $theme;
}

function contact_link(?string $contact = null): ?string
{
    $text = $contact ?? Ctx::$config->get(SetupConfig::CONTACT_LINK);
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

    if (str_contains($text, "/") && !str_starts_with($text, "/")) {
        return "https://$text";
    }

    return $text;
}

/**
 * Figure out PHP's internal memory limit
 */
function get_memory_limit(): int
{
    // thumbnail generation requires lots of memory
    $shimmie_limit = max(
        Ctx::$config->get(MediaConfig::MEM_LIMIT),
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
    $ini_files = ini_get('max_file_uploads');
    $ini_filesize = ini_get('upload_max_filesize');
    $ini_post = ini_get('post_max_size');

    $sys_files = empty($ini_files) ? null : parse_shorthand_int($ini_files);
    $sys_filesize = empty($ini_filesize) ? null : parse_shorthand_int($ini_filesize);
    $sys_post = empty($ini_post) ? null : parse_shorthand_int($ini_post);

    $conf_files = Ctx::$config->get(UploadConfig::COUNT);
    $conf_filesize = Ctx::$config->get(UploadConfig::SIZE);
    $conf_post = $conf_files * $conf_filesize;

    $limits = [
        'files' => $sys_files,
        'filesize' => $sys_filesize,
        'post' => $sys_post,
        'shm_files' => min($conf_files, $sys_files ?? PHP_INT_MAX),
        'shm_filesize' => min($conf_filesize, $sys_filesize ?? PHP_INT_MAX),
        'shm_post' => min($conf_post, $sys_post ?? PHP_INT_MAX),
    ];

    return $limits;
}

/**
 * A shorthand way to send a TextFormattingEvent and get the results.
 */
function format_text(string $string): HTMLElement
{
    $event = send_event(new TextFormattingEvent($string));
    return $event->getFormattedHTML();
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
    return microtime(true);
}

/**
 * Truncate a filename to a maximum length, preserving the extension.
 *
 * @param ?string $filename The filename to truncate, or null.
 * @param int $max_len The maximum length of the filename, including the extension.
 * @return ($filename is null ? null : string) The truncated filename, or null if the input was null.
 */
function truncate_filename(?string $filename, int $max_len = 250): ?string
{
    if ($filename === null) {
        return null;
    }
    if (strlen($filename) > $max_len) {
        // remove extension, truncate filename, apply extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = substr($filename, 0, $max_len - strlen($ext) - 1) . '.' . $ext;
    }
    return $filename;
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
 * @return array{time:float,dbtime:float,mem_mb:float,files:int,query_count:int,event_count:int,cache_hits:int,cache_misses:int,version:string}
 */
function get_debug_info_arr(): array
{
    return [
        "time" => round(ftime() - $_SERVER["REQUEST_TIME_FLOAT"], 2),
        "dbtime" => round(Ctx::$database->dbtime, 2),
        "mem_mb" => round(((memory_get_peak_usage(true) + 512) / 1024) / 1024, 2),
        "files" => count(get_included_files()),
        "query_count" => Ctx::$database->query_count,
        // "query_log" => Ctx::$database->queries,
        "event_count" => Ctx::$event_bus->event_count,
        "cache_hits" => Ctx::$cache->get("__etc_cache_hits"),
        "cache_misses" => Ctx::$cache->get("__etc_cache_misses"),
        "version" => SysConfig::getVersion(),
    ];
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Request initialisation stuff                                              *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * @param Path[] $files
 */
function require_all(array $files): void
{
    foreach ($files as $filename) {
        require_once $filename->str();
    }
}

function _load_ext_files(): void
{
    $span = Ctx::$tracer->startSpan("Load Ext Files");
    require_all(array_merge(
        Filesystem::zglob("ext/*/info.php"),
        Filesystem::zglob("ext/*/events.php"),
        Filesystem::zglob("ext/*/config.php"),
        Filesystem::zglob("ext/*/permissions.php"),
        Filesystem::zglob("ext/*/theme.php"),
        Filesystem::zglob("ext/*/main.php"),
    ));
    $span->end();
}

function _load_theme_files(): void
{
    $span = Ctx::$tracer->startSpan("Load Theme Files");
    $theme = get_theme();
    require_once('themes/'.$theme.'/page.class.php');
    require_all(Filesystem::zglob('themes/'.$theme.'/*.theme.php'));
    $span->end();
}

function _set_up_shimmie_environment(): void
{
    if (file_exists("images") && !file_exists("data/images")) {
        die("As of Shimmie 2.7 images and thumbs should be moved to data/images and data/thumbs");
    }

    if (SysConfig::getTimezone()) {
        date_default_timezone_set(SysConfig::getTimezone());
    }
}

/**
 * Used to display fatal errors to the web user.
 */
function _fatal_error(\Throwable $e): void
{
    $version = SysConfig::getVersion();
    $message = $e->getMessage();
    $phpver = phpversion();

    //$hash = exec("git rev-parse HEAD");
    //$h_hash = $hash ? "<p><b>Hash:</b> $hash" : "";
    //'.$h_hash.'

    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
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

        $db_info = null;
        if (is_a($e, DatabaseException::class)) {
            $db_info = emptyHTML(
                P(B("Query: "), $query),
                P(B("Args: "), var_export($e->args, true))
            );
        }

        if ($code >= 500) {
            error_log("Shimmie Error: $message (Query: $query)\n{$e->getTraceAsString()}");
        }

        header("HTTP/1.0 $code Error");

        $html = HTML(
            ["lang" => "en"],
            HEAD(
                TITLE("Internal Error")
            ),
            BODY(
                H1("Internal Error"),
                P(B("Message: "), $e::class . ": " . $message),
                $db_info,
                P(B("Version: "), "$version (on $phpver)"),
                P(B("Stack Trace:")),
                PRE(CODE($e->getTraceAsString()))
            )
        );

        echo "<!doctype html>\n" . $html;
    }
}

function _get_user(): User
{
    $my_user = null;
    /** @var ?string $auth */
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (!is_null($auth)) {
        $parts = explode(" ", $auth, 2);
        if (count($parts) === 2 && $parts[0] === "Bearer") {
            $parts = explode(":", $parts[1], 2);
            if (count($parts) === 2) {
                $my_user = User::by_session($parts[0], $parts[1]);
            }
        }
    }
    if (is_null($my_user) && Ctx::$page->get_cookie("user") && Ctx::$page->get_cookie("session")) {
        $my_user = User::by_session(Ctx::$page->get_cookie("user"), Ctx::$page->get_cookie("session"));
    }
    if (is_null($my_user)) {
        $my_user = User::get_anonymous();
    }

    return $my_user;
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* HTML Generation                                                           *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Make a form tag with relevant auth token and stuff
 */
function make_form(Url $target, bool $multipart = false, string $form_id = "", string $onsubmit = "", string $name = ""): string
{
    $at = Ctx::$user->get_auth_token();

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
    $factor = (int)floor((strlen(strval($bytes)) - 1) / 3);
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

function shm_tempnam(string $prefix = ""): Path
{
    if (!is_dir("data/temp")) {
        mkdir("data/temp");
    }
    $temp = \Safe\realpath("data/temp");
    return new Path(\Safe\tempnam($temp, $prefix));
}

function shm_tempdir(string $prefix = ""): Path
{
    $temp = shm_tempnam($prefix);
    $temp->unlink();
    $temp->mkdir(0700, true);
    return new Path($temp->str() . "/");
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* URL Shortcuts                                                             *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Build a link to a search page for given terms,
 * with all the appropriate escaping
 *
 * @param search-term-array $terms
 */
function search_link(array $terms = [], int $page = 1): Url
{
    if ($terms) {
        $q = url_escape(SearchTerm::implode($terms));
        return make_link("post/list/$q/$page");
    } else {
        return make_link("post/list/$page");
    }
}

/**
 * @param page-string $page
 * @param QueryArray|array<string, string>|null $query
 * @param fragment-string $fragment
 */
function make_link(?string $page = null, QueryArray|array|null $query = null, ?string $fragment = null): Url
{
    if (is_null($query)) {
        $query = new QueryArray([]);
    }
    if (is_array($query)) {
        $query = new QueryArray($query);
    }
    return new Url(page: $page ?? Ctx::$config->get(SetupConfig::MAIN_PAGE), query: $query, fragment: $fragment);
}

/**
 * Figure out the current page from a link that make_link() generated
 *
 * SHIT: notes for the future, because the web stack is a pile of hacks
 *
 * - According to some specs, "/" is for URL dividers with heiracial
 *   significance and %2F is for slashes that are just slashes. This
 *   is what shimmie currently does - eg if you search for "AC/DC",
 *   the shimmie URL will be /post/list/AC%2FDC/1
 * - According to some other specs "/" and "%2F" are identical...
 * - PHP's $_GET[] automatically urldecodes the inputs so we can't
 *   tell the difference between q=foo/bar and q=foo%2Fbar
 * - REQUEST_URI contains the exact URI that was given to us, so we
 *   can parse it for ourselves
 * - <input type='hidden' name='q' value='post/list'> generates
 *   q=post%2Flist
 * - When apache is reverse-proxying https://external.com/img/index.php
 *   to http://internal:8000/index.php, Url::base() should return
 *   /img, however the URL in REQUEST_URI is /index.php, not /img/index.php
 *
 * This function should always return strings with no leading slashes
 *
 * @param ?url-string $uri
 * @return page-string
 */
function _get_query(?string $uri = null): string
{
    /** @var ?string $request_uri */
    $request_uri = $_SERVER['REQUEST_URI'] ?? null;
    $parsed_url = parse_url($uri ?? $request_uri ?? "");

    // if we're looking at http://site.com/.../index.php,
    // then get the query from the "q" parameter
    if (str_ends_with($parsed_url["path"] ?? "", "/index.php")) {
        // default to looking at the root
        $q = "";
        // We can't just do `$q = $_GET["q"] ?? "";`, we need to manually
        // parse the query string because PHP's $_GET does an extra round
        // of URL decoding, which we don't want
        foreach (explode('&', $parsed_url['query'] ?? "") as $z) {
            $qps = explode('=', $z, 2);
            if (count($qps) === 2 && $qps[0] === "q") {
                $q = $qps[1];
            }
        }
        // if we have no slashes, but do have an encoded
        // slash, then we _probably_ encoded too much
        if (!str_contains($q, "/") && str_contains($q, "%2F")) {
            $q = rawurldecode($q);
        }
    }

    // if we're looking at http://site.com/$INSTALL_DIR/$PAGE,
    // then get the query from the path
    else {
        $base = (string)Url::base();
        $q = $parsed_url["path"] ?? "";

        // sometimes our public URL is /img/foo/bar but after
        // reverse-proxying shimmie only sees /foo/bar, so only
        // strip off the /img if it's actually there
        if (str_starts_with($q, $base)) {
            $q = substr($q, strlen($base));
        }

        // whether we are /img/foo/bar or /foo/bar, we still
        // want to remove the leading slash
        $q = ltrim($q, "/");
    }

    assert(!str_starts_with($q, "/"));
    return $q;
}

/**
 * @param non-empty-array<int|null> $comparison
 */
function compare_file_bytes(Path $file_name, array $comparison): bool
{
    $size = $file_name->filesize();
    $cc = count($comparison);
    if ($size < $cc) {
        // Can't match because it's too small
        return false;
    }

    if (($fh = @fopen($file_name->str(), 'rb'))) {
        try {
            $chunk = \Safe\unpack("C*", \Safe\fread($fh, $cc));

            for ($i = 0; $i < $cc; $i++) {
                $byte = $comparison[$i];
                if ($byte === null) {
                    continue;
                } else {
                    $fileByte = $chunk[$i + 1];
                    if ($fileByte !== $byte) {
                        return false;
                    }
                }
            }
            return true;
        } finally {
            @fclose($fh);
        }
    } else {
        throw new MediaException("Unable to open file for byte check: {$file_name->str()}");
    }
}
