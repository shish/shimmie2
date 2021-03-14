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
use MicroHTML\HTMLElement;

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

function contact_link(): ?string
{
    global $config;
    $text = $config->get_string('contact_link');
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

function load_balance_url(string $tmpl, string $hash, int $n=0): string
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
            $flexihash = new Flexihash\Flexihash();
            foreach (explode(",", $opts) as $opt) {
                $parts = explode("=", $opt);
                $parts_count = count($parts);
                $opt_val = "";
                $opt_weight = 0;
                if ($parts_count === 2) {
                    $opt_val = $parts[0];
                    $opt_weight = $parts[1];
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

function fetch_url(string $url, string $mfile): ?array
{
    global $config;

    if ($config->get_string(UploadConfig::TRANSLOAD_ENGINE) === "curl" && function_exists("curl_init")) {
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
            return null;
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = http_parse_headers(implode("\n", preg_split('/\R/', rtrim(substr($response, 0, $header_size)))));
        $body = substr($response, $header_size);

        curl_close($ch);
        fwrite($fp, $body);
        fclose($fp);

        return $headers;
    }

    if ($config->get_string(UploadConfig::TRANSLOAD_ENGINE) === "wget") {
        $s_url = escapeshellarg($url);
        $s_mfile = escapeshellarg($mfile);
        system("wget --no-check-certificate $s_url --output-document=$s_mfile");

        return file_exists($mfile) ? ["ok"=>"true"] : null;
    }

    if ($config->get_string(UploadConfig::TRANSLOAD_ENGINE) === "fopen") {
        $fp_in = @fopen($url, "r");
        $fp_out = fopen($mfile, "w");
        if (!$fp_in || !$fp_out) {
            return null;
        }
        $length = 0;
        while (!feof($fp_in) && $length <= $config->get_int(UploadConfig::SIZE)) {
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
                if ($category!="" && !str_contains($tag, ":")) {
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


function join_url(string $base, string ...$paths): string
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

function remove_empty_dirs(string $dir): bool
{
    assert(!empty($dir));

    $result = true;

    if (!is_dir($dir)) {
        return false;
    }

    $items = array_diff(
        scandir(
            $dir
        ),
        ['..', '.']
    );
    foreach ($items as $item) {
        $path = join_path($dir, $item);
        if (is_dir($path)) {
            $result = $result && remove_empty_dirs($path);
        } else {
            $result = false;
        }
    }
    if ($result===true) {
        $result = rmdir($dir);
    }
    return $result;
}


function get_files_recursively(string $dir): array
{
    assert(!empty($dir));

    if (!is_dir($dir)) {
        return [];
    }

    $things = array_diff(
        scandir(
            $dir
        ),
        ['..', '.']
    );

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

/** @privatesection
 * @noinspection PhpIncludeInspection
 */

function require_all(array $files): void
{
    foreach ($files as $filename) {
        require_once $filename;
    }
}

function _load_core_files()
{
    require_all(array_merge(
        zglob("core/*.php"),
        zglob("core/imageboard/*.php"),
        zglob("ext/*/info.php")
    ));
}

function _load_theme_files()
{
    require_all(_get_themelet_files(get_theme()));
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
    $tracer_enabled = constant('TRACE_FILE')!==null;
}


function _get_themelet_files(string $_theme): array
{
    $base_themelets = [];
    $base_themelets[] = 'themes/'.$_theme.'/page.class.php';
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
    $query = is_subclass_of($e, "SCoreException") ? $e->query : null;

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

        if ($query) {
            print("Query:   {$query}\n");
        }

        print("Version: $version (on $phpver)\n");
    } else {
        $q = $query ? "" : "<p><b>Query:</b> " . html_escape($query);
        header("HTTP/1.0 500 Internal Error");
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

function SHM_FORM(string $target, string $method="POST", bool $multipart=false, string $form_id="", string $onsubmit=""): HTMLElement
{
    global $user;

    $attrs = [
        "action"=>make_link($target),
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

function SHM_SIMPLE_FORM($target, ...$children): HTMLElement
{
    $form = SHM_FORM($target);
    $form->appendChild(emptyHTML(...$children));
    return $form;
}

function SHM_SUBMIT(string $text): HTMLElement
{
    return INPUT(["type"=>"submit", "value"=>$text]);
}

function SHM_COMMAND_EXAMPLE(string $ex, string $desc): HTMLElement
{
    return DIV(
        ["class"=>"command_example"],
        PRE($ex),
        P($desc)
    );
}

function SHM_USER_FORM(User $duser, string $target, string $title, $body, $foot): HTMLElement
{
    if (is_string($foot)) {
        $foot = TFOOT(TR(TD(["colspan"=>"2"], INPUT(["type"=>"submit", "value"=>$foot]))));
    }
    return SHM_SIMPLE_FORM(
        $target,
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

const BYTE_DENOMINATIONS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
function human_filesize(int $bytes, $decimals = 2): string
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
