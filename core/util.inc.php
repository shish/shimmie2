<?php
require_once "lib/recaptchalib.php";
require_once "lib/securimage/securimage.php";

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Input / Output Sanitising                                                 *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Make some data safe for printing into HTML
 *
 * @retval string
 */
function html_escape($input) {
	return htmlentities($input, ENT_QUOTES, "UTF-8");
}

/**
 * Make sure some data is safe to be used in integer context
 *
 * @retval int
 */
function int_escape($input) {
	return (int)$input;
}

/**
 * Make sure some data is safe to be used in URL context
 *
 * @retval string
 */
function url_escape($input) {
	$input = str_replace('^', '^^', $input);
	$input = str_replace('/', '^s', $input);
	$input = str_replace('\\', '^b', $input);
	$input = rawurlencode($input);
	return $input;
}

/**
 * Make sure some data is safe to be used in SQL context
 *
 * @retval string
 */
function sql_escape($input) {
	global $database;
	return $database->db->Quote($input);
}


/**
 * Turn all manner of HTML / INI / JS / DB booleans into a PHP one
 *
 * @retval boolean
 */
function bool_escape($input) {
	$input = strtolower($input);
	return (
		$input == "y" ||
		$input == "yes" ||
		$input == "t" ||
		$input == "true" ||
		$input == "on" ||
		$input == 1 ||
		$input == true
	);
}

/**
 * Some functions require a callback function for escaping,
 * but we might not want to alter the data
 *
 * @retval string
 */
function no_escape($input) {
	return $input;
}

/**
 * Turn a human readable filesize into an integer, eg 1KB -> 1024
 *
 * @retval int
 */
function parse_shorthand_int($limit) {
	if(is_numeric($limit)) {
		return (int)$limit;
	}

	if(preg_match('/^([\d\.]+)([gmk])?b?$/i', "$limit", $m)) {
		$value = $m[1];
		if (isset($m[2])) {
			switch(strtolower($m[2])) {
				case 'g': $value *= 1024;  # fallthrough
				case 'm': $value *= 1024;  # fallthrough
				case 'k': $value *= 1024; break;
				default: $value = -1;
			}
		}
		return (int)$value;
	} else {
		return -1;
	}
}

/**
 * Turn an integer into a human readable filesize, eg 1024 -> 1KB
 *
 * @retval string
 */
function to_shorthand_int($int) {
	if($int >= pow(1024, 3)) {
		return sprintf("%.1fGB", $int / pow(1024, 3));
	}
	else if($int >= pow(1024, 2)) {
		return sprintf("%.1fMB", $int / pow(1024, 2));
	}
	else if($int >= 1024) {
		return sprintf("%.1fKB", $int / 1024);
	}
	else {
		return "$int";
	}
}


/**
 * Turn a date into a time, a date, an "X minutes ago...", etc
 *
 * @retval string
 */
function autodate($date, $html=true) {
	$cpu = date('c', strtotime($date));
	$hum = date('F j, Y', strtotime($date));
	return ($html ? "<time datetime='$cpu'>$hum</time>" : $hum);
}


/**
 * Return a pluraliser if necessary
 *
 * @retval string
 */
function plural($num, $single_form="", $plural_form="s") {
	return ($num == 1) ? $single_form : $plural_form;
}


/**
 * Different databases have different ways to represent booleans; this
 * will try and standardise them
 */
function undb_bool($val) {
	if($val === true  || $val == 'Y' || $val == 'y' || $val == 'T' || $val == 't' || $val === 1) return true;
	if($val === false || $val == 'N' || $val == 'n' || $val == 'F' || $val == 'f' || $val === 0) return false;
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* HTML Generation                                                           *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Figure out the correct way to link to a page, taking into account
 * things like the nice URLs setting.
 *
 * eg make_link("post/list") becomes "/v2/index.php?q=post/list"
 *
 * @retval string
 */
function make_link($page=null, $query=null) {
	global $config;

	if(is_null($page)) $page = $config->get_string('main_page');

	if(FORCE_NICE_URLS || $config->get_bool('nice_urls', false)) {
		#$full = "http://" . $_SERVER["SERVER_NAME"] . $_SERVER["PHP_SELF"];
		$full = $_SERVER["PHP_SELF"];
		$base = str_replace("/index.php", "", $full);
	}
	else {
		$base = "./index.php?q=";
	}

	if(is_null($query)) {
		return str_replace("//", "/", "$base/$page");
	}
	else {
		if(strpos($base, "?")) {
			return "$base/$page&$query";
		}
		else if(strpos($query, "#") === 0) {
			return "$base/$page$query";
		}
		else {
			return "$base/$page?$query";
		}
	}
}


/**
 * Take the current URL and modify some paramaters
 *
 * @retval string
 */
function modify_current_url($changes) {
	// SHIT: PHP is officially the worst web API ever because it does not
	// have a built-in function to do this.

	// SHIT: parse_str is magically retarded; not only is it a useless name, it also
	// didn't return the parsed array, preferring to overwrite global variables with
	// whatever data the user supplied. Thankfully, 4.0.3 added an extra option to
	// give it an array to use...
	$params = array();
	parse_str($_SERVER['QUERY_STRING'], $params);

	if(isset($changes['q'])) {
		$base = $changes['q'];
		unset($changes['q']);
	}
	else {
		$base = $_GET['q'];
	}

	if(isset($params['q'])) {
		unset($params['q']);
	}

	foreach($changes as $k => $v) {
		if(is_null($v) and isset($params[$k])) unset($params[$k]);
		$params[$k] = $v;
	}

	return make_link($base, http_build_query($params));
}


/**
 * Turn a relative link into an absolute one, including hostname
 *
 * @retval string
 */
function make_http($link) {
	if(strpos($link, "ttp://") > 0) return $link;
	if(strlen($link) > 0 && $link[0] != '/') $link = get_base_href().'/'.$link;
	$link = "http://".$_SERVER["HTTP_HOST"].$link;
	$link = str_replace("/./", "/", $link);
	return $link;
}

/**
 * Make a form tag with relevant auth token and stuff
 * (Added optional Form ID field for helping jquery.)
 *
 * @retval string
 */
function make_form($target, $method="POST", $multipart=False, $form_id="", $onsubmit="") {
	global $user;
	$auth = $user->get_auth_html();
	$extra = empty($form_id) ? '' : " id='$form_id'";
	if($multipart) {
		$extra .= " enctype='multipart/form-data'";
	}
	if($onsubmit) {
		$extra .= " onsubmit='$onsubmit'";
	}
	return "<form action='$target' method='$method'$extra>$auth";
}

/**
 * Make a link to a static file in the current theme's
 * directory
 */
function theme_file($filepath) {
	global $config;
	$theme = $config->get_string("theme","default");
	return make_link("themes/$theme/$filepath");
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* CAPTCHA abstraction                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function captcha_get_html() {
	global $config, $user;

	if(DEBUG && ip_in_range($_SERVER['REMOTE_ADDR'], "127.0.0.0/8")) return "";

	$captcha = "";
	if($user->is_anonymous() && $config->get_bool("comment_captcha")) {
		$rpk = $config->get_string("api_recaptcha_privkey");
		if(!empty($rpk)) {
			$captcha = recaptcha_get_html($rpk);
		}
		else {
			session_start();
			$securimg = new Securimage();
			$base = get_base_href();
			$captcha = "<br/><img src='$base/lib/securimage/securimage_show.php?sid=". md5(uniqid(time())) ."'>".
				"<br/>CAPTCHA: <input type='text' name='code' value='' />";
		}
	}
	return $captcha;
}

function captcha_check() {
	global $config, $user;

	if(DEBUG && ip_in_range($_SERVER['REMOTE_ADDR'], "127.0.0.0/8")) return true;

	if($user->is_anonymous() && $config->get_bool("comment_captcha")) {
		$rpk = $config->get_string('api_recaptcha_pubkey');
		if(!empty($rpk)) {
			$resp = recaptcha_check_answer(
					$rpk,
					$_SERVER["REMOTE_ADDR"],
					$_POST["recaptcha_challenge_field"],
					$_POST["recaptcha_response_field"]);

			if(!$resp->is_valid) {
				log_info("core", "Captcha failed (ReCaptcha): " . $resp->error);
				return false;
			}
		}
		else {
			session_start();
			$securimg = new Securimage();
			if($securimg->check($_POST['code']) == false) {
				log_info("core", "Captcha failed (Securimage)");
				return false;
			}
		}
	}

	return true;
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Misc                                                                      *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * @private
 */
function _version_check() {
	if(version_compare(PHP_VERSION, "5.0.0") == -1) {
		print "
Currently SCore Engine doesn't support versions of PHP lower than 5.0.0 --
PHP4 and earlier are officially dead according to their creators,
please tell your host to upgrade.
";
		exit;
	}
}

/**
 * @private
 */
function check_cli() {
	if(isset($_SERVER['REMOTE_ADDR'])) {
		print "This script is to be run from the command line only.";
		exit;
	}
	$_SERVER['REMOTE_ADDR'] = "127.0.0.1";
}

/**
 * $db is the connection object
 *
 * @private
 */
function _count_execs($db, $sql, $inputarray) {
	global $_execs;
	if(DEBUG) {
		$fp = @fopen("data/sql.log", "a");
		if($fp) {
			if(is_array($inputarray)) {
				fwrite($fp, preg_replace('/\s+/msi', ' ', $sql)." -- ".join(", ", $inputarray)."\n");
			}
			else {
				fwrite($fp, preg_replace('/\s+/msi', ' ', $sql)."\n");
			}
			fclose($fp);
		}
		else {
			# WARNING:
			# SQL queries happen before the event system is fully initialised
			# (eg, "select theme from config" happens before "load themes"),
			# so using the event system to report an error will create some
			# really weird looking bugs.
			#
			#log_error("core", "failed to open sql.log for appending");
		}
	}
	if (!is_array($inputarray)) $_execs++;
	# handle 2-dimensional input arrays
	else if (is_array(reset($inputarray))) $_execs += sizeof($inputarray);
	else $_execs++;
	# in PHP4.4 and PHP5, we need to return a value by reference
	$null = null; return $null;
}

/**
 * Find the theme object for a given extension
 */
function get_theme_object(Extension $class, $fatal=true) {
	$base = get_class($class);
	if(class_exists("Custom{$base}Theme")) {
		$class = "Custom{$base}Theme";
		return new $class();
	}
	elseif ($fatal || class_exists("{$base}Theme")) {
		$class = "{$base}Theme";
		return new $class();
	} else {
		return false;
	}
}

/**
 * Compare two Block objects, used to sort them before being displayed
 *
 * @retval int
 */
function blockcmp(Block $a, Block $b) {
	if($a->position == $b->position) {
		return 0;
	}
	else {
		return ($a->position > $b->position);
	}
}

/**
 * Figure out PHP's internal memory limit
 *
 * @retval int
 */
function get_memory_limit() {
	global $config;

	// thumbnail generation requires lots of memory
	$default_limit = 8*1024*1024;
	$shimmie_limit = parse_shorthand_int($config->get_int("thumb_mem_limit"));
	if($shimmie_limit < 3*1024*1024) {
		// we aren't going to fit, override
		$shimmie_limit = $default_limit;
	}

	ini_set("memory_limit", $shimmie_limit);
	$memory = parse_shorthand_int(ini_get("memory_limit"));

	// changing of memory limit is disabled / failed
	if($memory == -1) {
		$memory = $default_limit;
	}

	assert($memory > 0);

	return $memory;
}

/**
 * Get the currently active IP, masked to make it not change when the last
 * octet or two change, for use in session cookies and such
 *
 * @retval string
 */
function get_session_ip($config) {
    $mask = $config->get_string("session_hash_mask", "255.255.0.0");
    $addr = $_SERVER['REMOTE_ADDR'];
    $addr = inet_ntop(inet_pton($addr) & inet_pton($mask));
    return $addr;
}

/**
 * similar to $_COOKIE[$name], but $name has the site-wide cookie
 * prefix prepended to it, eg username -> shm_username, to prevent
 * conflicts from multiple installs within one domain.
 */
function get_prefixed_cookie($name) {
	global $config;
	$full_name = COOKIE_PREFIX."_".$name;
	if(isset($_COOKIE[$full_name])) {
		return $_COOKIE[$full_name];
	}
	else {
		return null;
	}
}

/**
 * The counterpart for get_prefixed_cookie, this works like php's
 * setcookie method, but prepends the site-wide cookie prefix to
 * the $name argument before doing anything.
 */
function set_prefixed_cookie($name, $value, $time, $path) {
	global $config;
	$full_name = $config->get_string('cookie_prefix','shm')."_".$name;
	setcookie($full_name, $value, $time, $path);
}

/**
 * Figure out the path to the shimmie install root.
 *
 * PHP really, really sucks.
 *
 * @retval string
 */
function get_base_href() {
	$possible_vars = array('SCRIPT_NAME', 'PHP_SELF', 'PATH_INFO', 'ORIG_PATH_INFO');
	$ok_var = null;
	foreach($possible_vars as $var) {
		if(substr($_SERVER[$var], -4) == '.php') {
			$ok_var = $_SERVER[$var];
			break;
		}
	}
	assert(!empty($ok_var));
	$dir = dirname($ok_var);
	if($dir == "/" || $dir == "\\") $dir = "";
	return $dir;
}

/**
 * A shorthand way to send a TextFormattingEvent and get the
 * results
 *
 * @retval string
 */
function format_text($string) {
	$tfe = new TextFormattingEvent($string);
	send_event($tfe);
	return $tfe->formatted;
}

function warehouse_path($base, $hash, $create=true) {
	$ab = substr($hash, 0, 2);
	$cd = substr($hash, 2, 2);
	if(WH_SPLITS == 2) {
		$pa = "$base/$ab/$cd/$hash";
	}
	else {
		$pa = "$base/$ab/$hash";
	}
	if($create && !file_exists(dirname($pa))) mkdir(dirname($pa), 0755, true);
	return $pa;
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Logging convenience                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

define("SCORE_LOG_CRITICAL", 50);
define("SCORE_LOG_ERROR", 40);
define("SCORE_LOG_WARNING", 30);
define("SCORE_LOG_INFO", 20);
define("SCORE_LOG_DEBUG", 10);
define("SCORE_LOG_NOTSET", 0);

/**
 * A shorthand way to send a LogEvent
 */
function log_msg($section, $priority, $message) {
	send_event(new LogEvent($section, $priority, $message));
}

function log_debug($section, $message) {log_msg($section, SCORE_LOG_DEBUG, $message);}
function log_info($section, $message)  {log_msg($section, SCORE_LOG_INFO, $message);}
function log_warning($section, $message) {log_msg($section, SCORE_LOG_WARNING, $message);}
function log_error($section, $message) {log_msg($section, SCORE_LOG_ERROR, $message);}
function log_critical($section, $message) {log_msg($section, SCORE_LOG_CRITICAL, $message);}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Things which should be in the core API                                    *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Remove an item from an array
 *
 * @retval array
 */
function array_remove($array, $to_remove) {
	$array = array_unique($array);
	$a2 = array();
	foreach($array as $existing) {
		if($existing != $to_remove) {
			$a2[] = $existing;
		}
	}
	return $a2;
}

/**
 * Adds an item to an array.
 *
 * Also removes duplicate values from the array.
 *
 * @retval array
 */
function array_add($array, $element) {
	// Could we just use array_push() ?
	//  http://www.php.net/manual/en/function.array-push.php
	$array[] = $element;
	$array = array_unique($array);
	return $array;
}

/**
 * Return the unique elements of an array, case insensitively
 *
 * @retval array
 */
function array_iunique($array) {
	$ok = array();
	foreach($array as $element) {
		$found = false;
		foreach($ok as $existing) {
			if(strtolower($element) == strtolower($existing)) {
				$found = true; break;
			}
		}
		if(!$found) {
			$ok[] = $element;
		}
	}
	return $ok;
}

/**
 * Figure out if an IP is in a specified range
 *
 * from http://uk.php.net/network
 *
 * @retval bool
 */
function ip_in_range($IP, $CIDR) {
	list ($net, $mask) = explode("/", $CIDR);

	$ip_net = ip2long ($net);
	$ip_mask = ~((1 << (32 - $mask)) - 1);

	$ip_ip = ip2long ($IP);

	$ip_ip_net = $ip_ip & $ip_mask;

	return ($ip_ip_net == $ip_net);
}

/**
 * Delete an entire file heirachy
 *
 * from a patch by Christian Walde; only intended for use in the
 * "extension manager" extension, but it seems to fit better here
 */
function deltree($f) {
	//Because Windows (I know, bad excuse)
	if (PHP_OS === 'WINNT') {
		$real = realpath($f);
		$path = realpath('./').'\\'.str_replace('/', '\\', $f);
		if ($path != $real) {
			rmdir($path);
		}
		else
		{
			foreach(glob($f.'/*') as $sf) {
				if (is_dir($sf) && !is_link($sf)) {
					deltree($sf);
				} else {
					unlink($sf);
				}
			}
			rmdir($f);
		}
	}
	else {
		if (is_link($f)) {
			unlink($f);
		}
		else if(is_dir($f)) {
			foreach(glob($f.'/*') as $sf) {
				if (is_dir($sf) && !is_link($sf)) {
					deltree($sf);
				} else {
					unlink($sf);
				}
			}
			rmdir($f);
		}
	}
}

/**
 * Copy an entire file heirachy
 *
 * from a comment on http://uk.php.net/copy
 */
function full_copy($source, $target) {
	if(is_dir($source)) {
		@mkdir($target);

		$d = dir($source);

		while(FALSE !== ($entry = $d->read())) {
			if($entry == '.' || $entry == '..') {
				continue;
			}

			$Entry = $source . '/' . $entry;
			if(is_dir($Entry)) {
				full_copy($Entry, $target . '/' . $entry);
				continue;
			}
			copy($Entry, $target . '/' . $entry);
		}
		$d->close();
	}
	else {
		copy($source, $target);
	}
}

/**
 * @private
 */
function weighted_random($weights) {
	$total = 0;
	foreach($weights as $k => $w) {
		$total += $w;
	}

	$r = mt_rand(0, $total);
	foreach($weights as $k => $w) {
		$r -= $w;
		if($r <= 0) {
			return $k;
		}
	}
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Event API                                                                 *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/** @private */
$_event_listeners = array();

/**
 * Register an Extension
 */
function add_event_listener(Extension $extension, $pos=50) {
	global $_event_listeners;
	while(isset($_event_listeners[$pos])) {
		$pos++;
	}
	$_event_listeners[$pos] = $extension;
}

/** @private */
$_event_count = 0;

/**
 * Send an event to all registered Extensions
 */
function send_event(Event $event) {
	global $_event_listeners, $_event_count;
	ctx_log_start(get_class($event));
	$my_event_listeners = $_event_listeners; // http://bugs.php.net/bug.php?id=35106
	ksort($my_event_listeners);
	foreach($my_event_listeners as $listener) {
		ctx_log_start(get_class($listener));
		$listener->receive_event($event);
		ctx_log_endok();
	}
	$_event_count++;
	ctx_log_endok();
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Debugging functions                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function get_debug_info() {
	global $config, $_event_count, $database, $_execs;

	if(function_exists('memory_get_usage')) {
		$i_mem = sprintf("%5.2f", ((memory_get_usage()+512)/1024)/1024);
	}
	else {
		$i_mem = "???";
	}
	if(function_exists('getrusage')) {
		$ru = getrusage();
		$i_utime = sprintf("%5.2f", ($ru["ru_utime.tv_sec"]*1e6+$ru["ru_utime.tv_usec"])/1000000);
		$i_stime = sprintf("%5.2f", ($ru["ru_stime.tv_sec"]*1e6+$ru["ru_stime.tv_usec"])/1000000);
	}
	else {
		$i_utime = "???";
		$i_stime = "???";
	}
	
	$i_files = count(get_included_files());
	$hits = $database->cache->get_hits();
	$miss = $database->cache->get_misses();
	
	$debug = "<br>Took $i_utime + $i_stime seconds and {$i_mem}MB of RAM";
	$debug .= "; Used $i_files files and $_execs queries";
	$debug .= "; Sent $_event_count events";
	$debug .= "; $hits cache hits and $miss misses";
	$debug .= "; Shimmie version ". VERSION .", SCore Version ". SCORE_VERSION;

	return $debug;
}

// print_obj ($object, $title, $return)
function print_obj($object,$title="Object Information", $return=false) {
	global $user;
	if(DEBUG && isset($_GET['debug']) && $user->is_admin()) {
		$pr = print_r($object,true);
		$count = substr_count($pr,"\n")<=25?substr_count($pr,"\n"):25;
		$pr = "<textarea rows='".$count."' cols='80'>$pr</textarea>";

		if($return) {
			return $pr;
		} else {
			global $page;
			$page->add_block(new Block($title,$pr,"main",1000));
			return true;
		}
	}
}

// preset tests.

// Prints the contents of $event->args, even though they are clearly visible in
// the URL bar.
function print_url_args() {
	global $event;
	print_obj($event->args,"URL Arguments");
}

// Prints all the POST data.
function print_POST() {
	print_obj($_POST,"\$_POST");
}

// Prints GET, though this is also visible in the url ( url?var&var&var)
function print_GET() {
	print_obj($_GET,"\$_GET");
}

$_last_time = microtime(true);
$_last_msg = null;
function timeline($text) {
	if(empty($_GET["timeline"])) return;
	global $_last_time, $_last_msg;
	$time = microtime(true);
	if($_last_msg) printf("TL: %s (%dms)<br>", $_last_msg, (int)(($time-$_last_time)*1000));
	$_last_time = $time;
	$_last_msg = $text;
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Request initialisation stuff                                              *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/** @privatesection */

function _stripslashes_r($arr) {
	return is_array($arr) ? array_map('_stripslashes_r', $arr) : stripslashes($arr);
}

function _sanitise_environment() {
	if(DEBUG) {
		error_reporting(E_ALL);
		assert_options(ASSERT_ACTIVE, 1);
		assert_options(ASSERT_BAIL, 1);
	}

	ob_start();

	if(get_magic_quotes_gpc()) {
		$_GET = _stripslashes_r($_GET);
		$_POST = _stripslashes_r($_POST);
		$_COOKIE = _stripslashes_r($_COOKIE);
	}
}

/**
 * Turn ^^ into ^ and ^s into /
 *
 * Necessary because various servers and various clients
 * think that / is special...
 */
function _decaret($str) {
	$out = "";
	for($i=0; $i<strlen($str); $i++) {
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

function _get_query_parts() {
	if(isset($_GET["q"])) {
		$path = $_GET["q"];
	}
	else if(isset($_SERVER["PATH_INFO"])) {
		$path = $_SERVER["PATH_INFO"];
	}
	else {
		$path = "";
	}

	while(strlen($path) > 0 && $path[0] == '/') {
		$path = substr($path, 1);
	}

	$parts = explode('/', $path);

	if(strpos($path, "^") === FALSE) {
		return $parts;
	}
	else {
		$unescaped = array();
		foreach($parts as $part) {
			$unescaped[] = _decaret($part);
		}
		return $unescaped;
	}
}

function _get_page_request() {
	global $config;
	$args = _get_query_parts();

	if(count($args) == 0 || strlen($args[0]) == 0) {
		$args = explode('/', $config->get_string('front_page'));
	}

	return new PageRequestEvent($args);
}

function _get_user() {
	global $config, $database;
	$user = null;
	if(get_prefixed_cookie("user") && get_prefixed_cookie("session")) {
	    $tmp_user = User::by_session(get_prefixed_cookie("user"), get_prefixed_cookie("session"));
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


$_cache_memcache = false;
$_cache_key = null;
$_cache_filename = null;

function _cache_active() {
	return (
		(CACHE_MEMCACHE || CACHE_DIR) &&
		$_SERVER["REQUEST_METHOD"] == "GET" &&
		!get_prefixed_cookie("session") &&
		!get_prefixed_cookie("nocache")
	);
}

function _cache_log($text) {
	$fp = @fopen("data/cache.log", "a");
	if($fp) {
		fputs($fp, $text);
		fclose($fp);
	}
}

function _start_cache() {
	global $_cache_memcache, $_cache_key, $_cache_filename;

	if(_cache_active()) {
		if(CACHE_MEMCACHE) {
			$_cache_memcache = new Memcache;
			$_cache_memcache->pconnect('localhost', 11211);
			$_cache_key = "uri:".$_SERVER["REQUEST_URI"];
			$data = $_cache_memcache->get($_cache_key);
			if(DEBUG) {
				$stat = $zdata ? "hit" : "miss";
				_cache_log(time() . " " . sprintf(" %-4s ", $stat) . $_cache_key . "\n");
			}
			if($data) {
				header("Content-type: text/html");
				print $data;
				exit;
			}
		}

		if(CACHE_DIR) {
			$_cache_hash = md5($_SERVER["QUERY_STRING"]);
			$ab = substr($_cache_hash, 0, 2);
			$cd = substr($_cache_hash, 2, 2);
			$_cache_filename = "data/$ab/$cd/$_cache_hash";

			if(!file_exists(dirname($_cache_filename))) {
				mkdir(dirname($_cache_filename), 0750, true);
			}
			if(file_exists($_cache_filename) && (filemtime($_cache_filename) > time() - 3600)) {
				$gmdate_mod = gmdate('D, d M Y H:i:s', filemtime($_cache_filename)) . ' GMT';

				if(isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
					$if_modified_since = preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]);

					if($if_modified_since == $gmdate_mod) {
						header("HTTP/1.0 304 Not Modified");
						header("Content-type: text/html");
						exit;
					}
				}
				else {
					header("Content-type: text/html");
					header("Last-Modified: $gmdate_mod");
					$zdata = @file_get_contents($_cache_filename);
					if(CACHE_MEMCACHE) {
						$_cache_memcache->set($_cache_hash, $zdata, 0, 600);
					}
					$data = @gzuncompress($zdata);
					if($data) {
						print $data;
						exit;
					}
				}
			}
			ob_start();
		}
	}
}

function _end_cache() {
	global $_cache_memcache, $_cache_key, $_cache_filename;

	if(_cache_active()) {
		$data = ob_get_contents();
		if(CACHE_MEMCACHE) {
			$_cache_memcache->set($_cache_key, $data, 0, 600);
		}
		if(CACHE_DIR) {
			$zdata = gzcompress($data, 2);
			file_put_contents($_cache_filename, $zdata);
		}
	}
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Code coverage                                                             *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function _start_coverage() {
	if(function_exists("xdebug_start_code_coverage")) {
		#xdebug_start_code_coverage(XDEBUG_CC_UNUSED|XDEBUG_CC_DEAD_CODE);
		xdebug_start_code_coverage(XDEBUG_CC_UNUSED);
	}
}

function _end_coverage() {
	if(function_exists("xdebug_get_code_coverage")) {
		if(!file_exists("data/coverage")) mkdir("data/coverage");
		$n = 0;
		$t = time();
		while(file_exists("data/coverage/$t.$n.log")) $n++;
		file_put_contents("data/coverage/$t.$n.log", serialize(xdebug_get_code_coverage()));
	}
}
?>
