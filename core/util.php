<?php
require_once "vendor/shish/libcontext-php/context.php";

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Misc                                                                      *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function mtimefile(string $file): string {
	$data_href = get_base_href();
	$mtime = filemtime($file);
	return "$data_href/$file?$mtime";
}

function get_theme(): string {
	global $config;
	$theme = $config->get_string("theme", "default");
	if(!file_exists("themes/$theme")) $theme = "default";
	return $theme;
}

function contact_link(): ?string {
	global $config;
	$text = $config->get_string('contact_link');
	if(is_null($text)) return null;

	if(
		startsWith($text, "http:") ||
		startsWith($text, "https:") ||
		startsWith($text, "mailto:")
	) {
		return $text;
	}

	if(strpos($text, "@")) {
		return "mailto:$text";
	}

	if(strpos($text, "/")) {
		return "http://$text";
	}

	return $text;
}

/**
 * Check if HTTPS is enabled for the server.
 */
function is_https_enabled(): bool {
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
}

/**
 * Compare two Block objects, used to sort them before being displayed
 */
function blockcmp(Block $a, Block $b): int {
	if($a->position == $b->position) {
		return 0;
	}
	else {
		return ($a->position > $b->position);
	}
}

/**
 * Figure out PHP's internal memory limit
 */
function get_memory_limit(): int {
	global $config;

	// thumbnail generation requires lots of memory
	$default_limit = 8*1024*1024;	// 8 MB of memory is PHP's default.
	$shimmie_limit = parse_shorthand_int($config->get_int("thumb_mem_limit"));

	if($shimmie_limit < 3*1024*1024) {
		// we aren't going to fit, override
		$shimmie_limit = $default_limit;
	}

	/*
	Get PHP's configured memory limit.
	Note that this is set to -1 for NO memory limit.

	http://ca2.php.net/manual/en/ini.core.php#ini.memory-limit
	*/
	$memory = parse_shorthand_int(ini_get("memory_limit"));

	if($memory == -1) {
		// No memory limit.
		// Return the larger of the set limits.
		return max($shimmie_limit, $default_limit);
	}
	else {
		// PHP has a memory limit set.
		if ($shimmie_limit > $memory) {
			// Shimmie wants more memory than what PHP is currently set for.

			// Attempt to set PHP's memory limit.
			if ( ini_set("memory_limit", $shimmie_limit) === false ) {
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
 * Get the currently active IP, masked to make it not change when the last
 * octet or two change, for use in session cookies and such
 */
function get_session_ip(Config $config): string {
	$mask = $config->get_string("session_hash_mask", "255.255.0.0");
	$addr = $_SERVER['REMOTE_ADDR'];
	$addr = inet_ntop(inet_pton($addr) & inet_pton($mask));
	return $addr;
}


/**
 * Set (or extend) a flash-message cookie.
 *
 * This can optionally be done at the same time as saving a log message with log_*()
 *
 * Generally one should flash a message in onPageRequest and log a message wherever
 * the action actually takes place (eg onWhateverElse) - but much of the time, actions
 * are taken from within onPageRequest...
 */
function flash_message(string $text, string $type="info") {
	global $page;
	$current = $page->get_cookie("flash_message");
	if($current) {
		$text = $current . "\n" . $text;
	}
	# the message should be viewed pretty much immediately,
	# so 60s timeout should be more than enough
	$page->add_cookie("flash_message", $text, time()+60, "/");
}

/**
 * A shorthand way to send a TextFormattingEvent and get the results.
 */
function format_text(string $string): string {
	$tfe = new TextFormattingEvent($string);
	send_event($tfe);
	return $tfe->formatted;
}

function warehouse_path(string $base, string $hash, bool $create=true): string {
	$ab = substr($hash, 0, 2);
	$cd = substr($hash, 2, 2);
	if(WH_SPLITS == 2) {
		$pa = 'data/'.$base.'/'.$ab.'/'.$cd.'/'.$hash;
	}
	else {
		$pa = 'data/'.$base.'/'.$ab.'/'.$hash;
	}
	if($create && !file_exists(dirname($pa))) mkdir(dirname($pa), 0755, true);
	return $pa;
}

function data_path(string $filename): string {
	$filename = "data/" . $filename;
	if(!file_exists(dirname($filename))) mkdir(dirname($filename), 0755, true);
	return $filename;
}

function transload(string $url, string $mfile): ?array {
	global $config;

	if($config->get_string("transload_engine") === "curl" && function_exists("curl_init")) {
		$ch = curl_init($url);
		$fp = fopen($mfile, "w");

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "Shimmie-".VERSION);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		$response = curl_exec($ch);

		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$headers = http_parse_headers(implode("\n", preg_split('/\R/', rtrim(substr($response, 0, $header_size)))));
		$body = substr($response, $header_size);

		curl_close($ch);
		fwrite($fp, $body);
		fclose($fp);

		return $headers;
	}

	if($config->get_string("transload_engine") === "wget") {
		$s_url = escapeshellarg($url);
		$s_mfile = escapeshellarg($mfile);
		system("wget --no-check-certificate $s_url --output-document=$s_mfile");

		return file_exists($mfile);
	}

	if($config->get_string("transload_engine") === "fopen") {
		$fp_in = @fopen($url, "r");
		$fp_out = fopen($mfile, "w");
		if(!$fp_in || !$fp_out) {
			return null;
		}
		$length = 0;
		while(!feof($fp_in) && $length <= $config->get_int('upload_size')) {
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
function manual_include(string $fname): ?string {
	static $included = array();

	if(!file_exists($fname)) return null;

	if(in_array($fname, $included)) return null;

	$included[] = $fname;

	print "$fname\n";

	$text = file_get_contents($fname);

	// we want one continuous file
	$text = str_replace('<'.'?php', '', $text);
	$text = str_replace('?'.'>',    '', $text);

	// most requires are built-in, but we want /lib separately
	$text = str_replace('require_', '// require_', $text);
	$text = str_replace('// require_once "lib', 'require_once "lib', $text);

	// @include_once is used for user-creatable config files
	$text = preg_replace('/@include_once "(.*)";/e', "manual_include('$1')", $text);

	return $text;
}


function path_to_tags(string $path): string {
	$matches = array();
	if(preg_match("/\d+ - (.*)\.([a-zA-Z]+)/", basename($path), $matches)) {
		$tags = $matches[1];
	}
	else {
		$tags = dirname($path);
		$tags = str_replace("/", " ", $tags);
		$tags = str_replace("__", " ", $tags);
		$tags = trim($tags);
	}
	return $tags;
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
function get_debug_info(): string {
	global $config, $_shm_event_count, $database, $_shm_load_start;

	$i_mem = sprintf("%5.2f", ((memory_get_peak_usage(true)+512)/1024)/1024);

	if($config->get_string("commit_hash", "unknown") == "unknown"){
		$commit = "";
	}
	else {
		$commit = " (".$config->get_string("commit_hash").")";
	}
	$time = sprintf("%.2f", microtime(true) - $_shm_load_start);
	$dbtime = sprintf("%.2f", $database->dbtime);
	$i_files = count(get_included_files());
	$hits = $database->cache->get_hits();
	$miss = $database->cache->get_misses();

	$debug = "<br>Took $time seconds (db:$dbtime) and {$i_mem}MB of RAM";
	$debug .= "; Used $i_files files and {$database->query_count} queries";
	$debug .= "; Sent $_shm_event_count events";
	$debug .= "; $hits cache hits and $miss misses";
	$debug .= "; Shimmie version ". VERSION . $commit; // .", SCore Version ". SCORE_VERSION;

	return $debug;
}

function log_slow() {
	global $_shm_load_start;
	if(!is_null(SLOW_PAGES)) {
		$_time = microtime(true) - $_shm_load_start;
		if($_time > SLOW_PAGES) {
			$_query = _get_query();
			$_dbg = get_debug_info();
			file_put_contents("data/slow-pages.log", "$_time $_query $_dbg\n", FILE_APPEND | LOCK_EX);
		}
	}
}

function score_assert_handler($file, $line, $code, $desc = null) {
	$file = basename($file);
	print("Assertion failed at $file:$line: $code ($desc)");
	/*
	print("<pre>");
	debug_print_backtrace();
	print("</pre>");
	*/
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Request initialisation stuff                                              *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/** @privatesection */

function _version_check() {
	if(MIN_PHP_VERSION) {
		if(version_compare(phpversion(), MIN_PHP_VERSION, ">=") === FALSE) {
			print "
Shimmie (SCore Engine) does not support versions of PHP lower than ".MIN_PHP_VERSION."
(PHP reports that it is version ".phpversion().")
If your web host is running an older version, they are dangerously out of
date and you should plan on moving elsewhere.
";
			exit;
		}
	}
}

function _sanitise_environment() {
	global $_shm_ctx;

	if(TIMEZONE) {
		date_default_timezone_set(TIMEZONE);
	}

	# ini_set('zend.assertions', 1);  // generate assertions
	ini_set('assert.exception', 1);  // throw exceptions when failed
	if(DEBUG) {
		error_reporting(E_ALL);
		assert_options(ASSERT_ACTIVE, 1);
		assert_options(ASSERT_BAIL, 1);
		assert_options(ASSERT_WARNING, 0);
		assert_options(ASSERT_QUIET_EVAL, 1);
		assert_options(ASSERT_CALLBACK, 'score_assert_handler');
	}

	$_shm_ctx = new Context();
	if(CONTEXT) {
		$_shm_ctx->set_log(CONTEXT);
	}

	if(COVERAGE) {
		_start_coverage();
		register_shutdown_function("_end_coverage");
	}

	ob_start();

	if(PHP_SAPI === 'cli' || PHP_SAPI == 'phpdbg') {
		if(isset($_SERVER['REMOTE_ADDR'])) {
			die("CLI with remote addr? Confused, not taking the risk.");
		}
		$_SERVER['REMOTE_ADDR'] = "0.0.0.0";
		$_SERVER['HTTP_HOST'] = "<cli command>";
	}
}


function _get_themelet_files(string $_theme): array {
	$base_themelets = array();
	if(file_exists('themes/'.$_theme.'/custompage.class.php')) $base_themelets[] = 'themes/'.$_theme.'/custompage.class.php';
	$base_themelets[] = 'themes/'.$_theme.'/layout.class.php';
	$base_themelets[] = 'themes/'.$_theme.'/themelet.class.php';

	$ext_themelets = zglob("ext/{".ENABLED_EXTS."}/theme.php");
	$custom_themelets = zglob('themes/'.$_theme.'/{'.ENABLED_EXTS.'}.theme.php');

	return array_merge($base_themelets, $ext_themelets, $custom_themelets);
}


/**
 * Used to display fatal errors to the web user.
 */
function _fatal_error(Exception $e) {
	$version = VERSION;
	$message = $e->getMessage();

	//$trace = var_dump($e->getTrace());

	//$hash = exec("git rev-parse HEAD");
	//$h_hash = $hash ? "<p><b>Hash:</b> $hash" : "";
	//'.$h_hash.'

	header("HTTP/1.0 500 Internal Error");
	echo '
<html>
	<head>
		<title>Internal error - SCore-'.$version.'</title>
	</head>
	<body>
		<h1>Internal Error</h1>
		<p><b>Message:</b> '.$message.'
		<p><b>Version:</b> '.$version.' (on '.phpversion().')
	</body>
</html>
';
}

/**
 * Turn ^^ into ^ and ^s into /
 *
 * Necessary because various servers and various clients
 * think that / is special...
 */
function _decaret(string $str): string {
	$out = "";
	$length = strlen($str);
	for($i=0; $i<$length; $i++) {
		if($str[$i] == "^") {
			$i++;
			if($str[$i] == "^") $out .= "^";
			if($str[$i] == "s") $out .= "/";
			if($str[$i] == "b") $out .= "\\";
		}
		else {
			$out .= $str[$i];
		}
	}
	return $out;
}

function _get_user(): User {
	global $config, $page;
	$user = null;
	if($page->get_cookie("user") && $page->get_cookie("session")) {
		$tmp_user = User::by_session($page->get_cookie("user"), $page->get_cookie("session"));
		if(!is_null($tmp_user)) {
			$user = $tmp_user;
		}
	}
	if(is_null($user)) {
		$user = User::by_id($config->get_int("anon_id", 0));
	}
	assert(!is_null($user));

	return $user;
}

function _get_query(): string {
	return (@$_POST["q"]?:@$_GET["q"])?:"/";
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Code coverage                                                             *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function _start_coverage(): void {
	if(function_exists("xdebug_start_code_coverage")) {
		#xdebug_start_code_coverage(XDEBUG_CC_UNUSED|XDEBUG_CC_DEAD_CODE);
		xdebug_start_code_coverage(XDEBUG_CC_UNUSED);
	}
}

function _end_coverage(): void {
	if(function_exists("xdebug_get_code_coverage")) {
		// Absolute path is necessary because working directory
		// inside register_shutdown_function is unpredictable.
		$absolute_path = dirname(dirname(__FILE__)) . "/data/coverage";
		if(!file_exists($absolute_path)) mkdir($absolute_path);
		$n = 0;
		$t = time();
		while(file_exists("$absolute_path/$t.$n.log")) $n++;
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
function show_ip(string $ip, string $ban_reason): string {
	global $user;
	$u_reason = url_escape($ban_reason);
	$u_end = url_escape("+1 week");
	$ban = $user->can("ban_ip") ? ", <a href='".make_link("ip_ban/list", "ip=$ip&reason=$u_reason&end=$u_end#add")."'>Ban</a>" : "";
	$ip = $user->can("view_ip") ? $ip.$ban : "";
	return $ip;
}

/**
 * Make a form tag with relevant auth token and stuff
 */
function make_form(string $target, string $method="POST", bool $multipart=False, string $form_id="", string $onsubmit=""): string {
	global $user;
	if($method == "GET") {
		$link = html_escape($target);
		$target = make_link($target);
		$extra_inputs = "<input type='hidden' name='q' value='$link'>";
	}
	else {
		$extra_inputs = $user->get_auth_html();
	}

	$extra = empty($form_id) ? '' : 'id="'. $form_id .'"';
	if($multipart) {
		$extra .= " enctype='multipart/form-data'";
	}
	if($onsubmit) {
		$extra .= ' onsubmit="'.$onsubmit.'"';
	}
	return '<form action="'.$target.'" method="'.$method.'" '.$extra.'>'.$extra_inputs;
}