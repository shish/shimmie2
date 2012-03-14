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
	/*
	 Side note, Casting to an integer is FASTER than using intval.
	 http://hakre.wordpress.com/2010/05/13/php-casting-vs-intval/
	*/
	return (int)$input;
}

/**
 * Make sure some data is safe to be used in URL context
 *
 * @retval string
 */
function url_escape($input) {
	if(is_null($input)) {
		return "";
	}
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
		$input === "y" ||
		$input === "yes" ||
		$input === "t" ||
		$input === "true" ||
		$input === "on" ||
		$input === 1 ||
		$input === true
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

// Original PHP code by Chirp Internet: www.chirp.com.au
// Please acknowledge use of this code by including this header.
function truncate($string, $limit, $break=" ", $pad="...") {
	// return with no change if string is shorter than $limit
	if(strlen($string) <= $limit) return $string;

	// is $break present between $limit and the end of the string?
	if(false !== ($breakpoint = strpos($string, $break, $limit))) {
		if($breakpoint < strlen($string) - 1) {
			$string = substr($string, 0, $breakpoint) . $pad;
		}
	}

	return $string;
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

	if(preg_match('/^([\d\.]+)([gmk])?b?$/i', (string)$limit, $m)) {
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
		return (string)$int;
	}
}


/**
 * Turn a date into a time, a date, an "X minutes ago...", etc
 *
 * @retval string
 */
function autodate($date, $html=true) {
	$cpu = date('c', strtotime($date));
	$hum = date('F j, Y; H:i', strtotime($date));
	return ($html ? "<time datetime='$cpu'>$hum</time>" : $hum);
}

/**
 * Check if a given string is a valid date-time. ( Format: yyyy-mm-dd hh:mm:ss )
 *
 * @retval boolean
 */
function isValidDateTime($dateTime) {
	if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $dateTime, $matches)) {
		if (checkdate($matches[2], $matches[3], $matches[1])) {
			return true;
		}
	}

	return false;
}

/**
 * Check if a given string is a valid date. ( Format: yyyy-mm-dd )
 *
 * @retval boolean
 */
function isValidDate($date) {
	if (preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $date, $matches)) {
		// checkdate wants (month, day, year)
		if (checkdate($matches[2], $matches[3], $matches[1])) {
			return true;
		}
	}

	return false;
}

/**
 * Give a HTML string which shows an IP (if the user is allowed to see IPs),
 * and a link to ban that IP (if the user is allowed to ban IPs)
 *
 * FIXME: also check that IP ban ext is installed
 *
 * @retval string
 */
function show_ip($ip, $ban_reason) {
	global $user;
	$u_reason = url_escape($ban_reason);
	$u_end = url_escape("+1 week");
	$ban = $user->can("ban_ip") ? ", <a href='".make_link("ip_ban/list", "ip=$ip&reason=$u_reason&end=$u_end#add")."'>Ban</a>" : "";
	$ip = $user->can("view_ip") ? $ip.$ban : "";
	return $ip;
}

/**
 * Different databases have different ways to represent booleans; this
 * will try and standardise them
 */
function undb_bool($val) {
	if($val === true  || $val == 'Y' || $val == 'y' || $val == 'T' || $val == 't' || $val === 1) return true;
	if($val === false || $val == 'N' || $val == 'n' || $val == 'F' || $val == 'f' || $val === 0) return false;
}

/**
 * Checks if a given string contains another at the beginning.
 *
 * @param $haystack String to examine.
 * @param $needle String to look for.
 * @retval bool
 */
function startsWith(/*string*/ $haystack, /*string*/ $needle) {
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}

/**
 * Checks if a given string contains another at the end.
 *
 * @param $haystack String to examine.
 * @param $needle String to look for.
 * @retval bool
 */
function endsWith(/*string*/ $haystack, /*string*/ $needle) {
	$length = strlen($needle);
	$start  = $length * -1; //negative
	return (substr($haystack, $start) === $needle);
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

	if(NICE_URLS || $config->get_bool('nice_urls', false)) {
		#$full = "http://" . $_SERVER["SERVER_NAME"] . $_SERVER["PHP_SELF"];
		$full = $_SERVER["PHP_SELF"];
		$base = str_replace("/".basename($_SERVER["SCRIPT_FILENAME"]), "", $full);
	}
	else {
		$base = "./".basename($_SERVER["SCRIPT_FILENAME"])."?q=";
	}

	if(is_null($query)) {
		return str_replace("//", "/", $base.'/'.$page );
	}
	else {
		if(strpos($base, "?")) {
			return $base .'/'. $page .'&'. $query;
		}
		else if(strpos($query, "#") === 0) {
			return $base .'/'. $page . $query;
		}
		else {
			return $base .'/'. $page .'?'. $query;
		}
	}
}


/**
 * Take the current URL and modify some paramaters
 *
 * @retval string
 */
function modify_current_url($changes) {
	return modify_url($_SERVER['QUERY_STRING'], $changes);
}

function modify_url($url, $changes) {
	// SHIT: PHP is officially the worst web API ever because it does not
	// have a built-in function to do this.

	// SHIT: parse_str is magically retarded; not only is it a useless name, it also
	// didn't return the parsed array, preferring to overwrite global variables with
	// whatever data the user supplied. Thankfully, 4.0.3 added an extra option to
	// give it an array to use...
	$params = array();
	parse_str($url, $params);

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
function make_http(/*string*/ $link) {
	if(strpos($link, "ttp://") > 0) return $link;
	if(strlen($link) > 0 && $link[0] != '/') $link = get_base_href().'/'.$link;
	$link = "http://".$_SERVER["HTTP_HOST"].$link;
	$link = str_replace("/./", "/", $link);
	return $link;
}

/**
 * Make a form tag with relevant auth token and stuff
 *
 * @retval string
 */
function make_form($target, $method="POST", $multipart=False, $form_id="", $onsubmit="") {
	global $user;
	$auth = $user->get_auth_html();
	$extra = empty($form_id) ? '' : 'id="'. $form_id .'"';
	if($multipart) {
		$extra .= " enctype='multipart/form-data'";
	}
	if($onsubmit) {
		$extra .= ' onsubmit="'.$onsubmit.'"';
	}
	return '<form action="'.$target.'" method="'.$method.'" '.$extra.'>'.$auth;
}

function mtimefile($file) {
	$data_href = get_base_href();
	$mtime = filemtime($file);
	return "$data_href/$file?$mtime";
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
				$_POST["recaptcha_response_field"]
			);

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
	if(version_compare(PHP_VERSION, "5.2.6") == -1) {
		print "
Currently SCore Engine doesn't support versions of PHP lower than 5.2.6 --
if your web host is running an older version, they are dangerously out of
date and you should plan on moving elsewhere.
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
	if((DEBUG_SQL === true) || (is_null(DEBUG_SQL) && @$_GET['DEBUG_SQL'])) {
		$fp = @fopen("data/sql.log", "a");
		if($fp) {
			if(isset($inputarray) && is_array($inputarray)) {
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
			if ( ini_set("memory_limit", $shimmie_limit) === FALSE ) {
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
 *
 * @retval string
 */
function get_session_ip(Config $config) {
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
function get_prefixed_cookie(/*string*/ $name) {
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
 * Figure out the path to the shimmie install directory.
 *
 * eg if shimmie is visible at http://foo.com/gallery, this
 * function should return /gallery
 *
 * PHP really, really sucks.
 *
 * @retval string
 */
function get_base_href() {
	$possible_vars = array('SCRIPT_NAME', 'PHP_SELF', 'PATH_INFO', 'ORIG_PATH_INFO');
	$ok_var = null;
	foreach($possible_vars as $var) {
		if(substr($_SERVER[$var], -4) === '.php') {
			$ok_var = $_SERVER[$var];
			break;
		}
	}
	assert(!empty($ok_var));
	$dir = dirname($ok_var);
	if($dir === "/" || $dir === "\\") $dir = "";
	return $dir;
}

/**
 * A shorthand way to send a TextFormattingEvent and get the
 * results
 *
 * @retval string
 */
function format_text(/*string*/ $string) {
	$tfe = new TextFormattingEvent($string);
	send_event($tfe);
	return $tfe->formatted;
}

function warehouse_path(/*string*/ $base, /*string*/ $hash, /*bool*/ $create=true) {
	$ab = substr($hash, 0, 2);
	$cd = substr($hash, 2, 2);
	if(WH_SPLITS == 2) {
		$pa = $base.'/'.$ab.'/'.$cd.'/'.$hash;
	}
	else {
		$pa = $base.'/'.$ab.'/'.$hash;
	}
	if($create && !file_exists(dirname($pa))) mkdir(dirname($pa), 0755, true);
	return $pa;
}

function transload($url, $mfile) {
	global $config;

	if($config->get_string("transload_engine") == "curl" && function_exists("curl_init")) {
		$ch = curl_init($url);
		$fp = fopen($mfile, "w");

		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "Shimmie-".VERSION);

		curl_exec($ch);
		curl_close($ch);
		fclose($fp);

		return true;
	}

	if($config->get_string("transload_engine") == "wget") {
		$s_url = escapeshellarg($url);
		$s_mfile = escapeshellarg($mfile);
		system("wget $s_url --output-document=$s_mfile");

		return file_exists($mfile);
	}

	if($config->get_string("transload_engine") == "fopen") {
		$fp = @fopen($url, "r");
		if(!$fp) {
			return false;
		}
		$data = "";
		$length = 0;
		while(!feof($fp) && $length <= $config->get_int('upload_size')) {
			$data .= fread($fp, 8192);
			$length = strlen($data);
		}
		fclose($fp);

		$fp = fopen($mfile, "w");
		fwrite($fp, $data);
		fclose($fp);

		return true;
	}

	return false;
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
function log_msg(/*string*/ $section, /*int*/ $priority, /*string*/ $message) {
	send_event(new LogEvent($section, $priority, $message));
}

// More shorthand ways of logging
function log_debug(/*string*/ $section, /*string*/ $message) {log_msg($section, SCORE_LOG_DEBUG, $message);}
function log_info(/*string*/ $section, /*string*/ $message)  {log_msg($section, SCORE_LOG_INFO, $message);}
function log_warning(/*string*/ $section, /*string*/ $message) {log_msg($section, SCORE_LOG_WARNING, $message);}
function log_error(/*string*/ $section, /*string*/ $message) {log_msg($section, SCORE_LOG_ERROR, $message);}
function log_critical(/*string*/ $section, /*string*/ $message) {log_msg($section, SCORE_LOG_CRITICAL, $message);}


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
	if(PHP_OS === 'WINNT') {
		$real = realpath($f);
		$path = realpath('./').'\\'.str_replace('/', '\\', $f);
		if($path != $real) {
			rmdir($path);
		}
		else {
			foreach(glob($f.'/*') as $sf) {
				if (is_dir($sf) && !is_link($sf)) {
					deltree($sf);
				}
				else {
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
				}
				else {
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


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Event API                                                                 *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/** @private */
$_event_listeners = array();

/**
 * Register an Extension
 */
function add_event_listener(Extension $extension, $pos=50, $events=array()) {
	global $_event_listeners;
	foreach($events as $event) {
		while(isset($_event_listeners[$event][$pos])) {
			$pos++;
		}
		$_event_listeners[$event][$pos] = $extension;
	}
}

/** @private */
$_event_count = 0;

/**
 * Send an event to all registered Extensions
 */
function send_event(Event $event) {
	global $_event_listeners, $_event_count;
	if(!isset($_event_listeners[get_class($event)])) return;
	$method_name = "on".str_replace("Event", "", get_class($event));

	ctx_log_start(get_class($event));
	// SHIT: http://bugs.php.net/bug.php?id=35106
	$my_event_listeners = $_event_listeners[get_class($event)];
	ksort($my_event_listeners);
	foreach($my_event_listeners as $listener) {
		ctx_log_start(get_class($listener));
		$listener->$method_name($event);
		ctx_log_endok();
	}
	$_event_count++;
	ctx_log_endok();
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Debugging functions                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

// SHIT by default this returns the time as a string. And it's not even a
// string representation of a number, it's two numbers separated by a space.
// What the fuck were the PHP developers smoking.
$_load_start = microtime(true);

/**
 * Collects some debug information (execution time, memory usage, queries, etc)
 * and formats it to stick in the footer of the page.
 *
 * @retval String of debug info to add to the page.
 */
function get_debug_info() {
	global $config, $_event_count, $database, $_execs, $_load_start;

	$i_mem = sprintf("%5.2f", ((memory_get_peak_usage(true)+512)/1024)/1024);

	if($config->get_string("commit_hash", "unknown") == "unknown"){
		$commit = "";
	}
	else {
		$commit = " (".$config->get_string("commit_hash").")";
	}
	$time = sprintf("%5.2f", microtime(true) - $_load_start);
	$i_files = count(get_included_files());
	$hits = $database->cache->get_hits();
	$miss = $database->cache->get_misses();
	
	$debug = "<br>Took $time seconds and {$i_mem}MB of RAM";
	$debug .= "; Used $i_files files and $_execs queries";
	$debug .= "; Sent $_event_count events";
	$debug .= "; $hits cache hits and $miss misses";
	$debug .= "; Shimmie version ". VERSION . $commit; // .", SCore Version ". SCORE_VERSION;

	return $debug;
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Request initialisation stuff                                              *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/** @privatesection */

function _stripslashes_r($arr) {
	return is_array($arr) ? array_map('_stripslashes_r', $arr) : stripslashes($arr);
}

function _sanitise_environment() {
	if(TIMEZONE) {
		date_default_timezone_set(TIMEZONE);
	}

	if(DEBUG) {
		error_reporting(E_ALL);
	}

	assert_options(ASSERT_ACTIVE, 1);
	assert_options(ASSERT_BAIL, 1);

	ob_start();

	if(get_magic_quotes_gpc()) {
		$_GET = _stripslashes_r($_GET);
		$_POST = _stripslashes_r($_POST);
		$_COOKIE = _stripslashes_r($_COOKIE);
	}

	if(php_sapi_name() === "cli") {
		global $argc, $argv;
		$_SERVER['REMOTE_ADDR'] = "0.0.0.0";
		$_SERVER['HTTP_HOST'] = "<cli command>";
		if($argc > 1) {
			$_GET['q'] = $argv[1];
		}
	}
}

function _get_themelet_files($_theme) {
	$themelets = array();

	if(file_exists('themes/'.$_theme.'/custompage.class.php')) $themelets[] = 'themes/'.$_theme.'/custompage.class.php';
	$themelets[] = 'themes/'.$_theme.'/layout.class.php';
	$themelets[] = 'themes/'.$_theme.'/themelet.class.php';

	$themelet_files = glob("ext/*/theme.php");
	foreach($themelet_files as $filename) {
		$themelets[] = $filename;
	}

	$custom_themelets = glob('themes/'.$_theme.'/*.theme.php');
	if($custom_themelets) {
		$m = array();
		foreach($custom_themelets as $filename) {
			if(preg_match('/themes\/'.$_theme.'\/(.*)\.theme\.php/',$filename,$m)
					&& in_array('ext/'.$m[1].'/theme.php', $themelets)) {
				$themelets[] = $filename;
			}
		}
	}

	return $themelets;
}

function _load_extensions() {
	global $_event_listeners;

	ctx_log_start("Loading extensions");

	if(COMPILE_ELS && file_exists("data/event_listeners.php")) {
		require_once("data/event_listeners.php");
	}
	else {
		foreach(get_declared_classes() as $class) {
			$rclass = new ReflectionClass($class);
			if($rclass->isAbstract()) {
				// don't do anything
			}
			elseif(is_subclass_of($class, "Extension")) {
				$c = new $class();
				$c->i_am($c);
				$my_events = array();
				foreach(get_class_methods($c) as $method) {
					if(substr($method, 0, 2) == "on") {
						$my_events[] = substr($method, 2) . "Event";
					}
				}
				add_event_listener($c, $c->get_priority(), $my_events);
			}
		}

		if(COMPILE_ELS) {
			$p = "<"."?php\n";

			foreach(get_declared_classes() as $class) {
				$rclass = new ReflectionClass($class);
				if($rclass->isAbstract()) {}
				elseif(is_subclass_of($class, "Extension")) {
					$p .= "\$$class = new $class(); ";
					$p .= "\${$class}->i_am(\$$class);\n";
				}
			}

			$p .= "\$_event_listeners = array(\n";
			foreach($_event_listeners as $event => $listeners) {
				$p .= "\t'$event' => array(\n";
				foreach($listeners as $id => $listener) {
					$p .= "\t\t$id => \$".get_class($listener).",\n";
				}
				$p .= "\t),\n";
			}
			$p .= ");\n";

			$p .= "?".">";
			file_put_contents("data/event_listeners.php", $p);
		}
	}

	ctx_log_endok();
}

/**
 * Used to display fatal errors to the web user.
 */
function _fatal_error(Exception $e) {
	$version = VERSION;
	$message = $e->getMessage();
	//$trace = var_dump($e->getTrace());
	header("HTTP/1.0 500 Internal Error");
	echo '
<html>
	<head>
		<title>Internal error - SCore-'.$version.'</title>
	</head>
	<body>
		<h1>Internal Error</h1>
		<p>'.$message.'
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
function _decaret($str) {
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

	if(empty($args) || strlen($args[0]) === 0) {
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
			$_cache_filename = "data/http_cache/$ab/$cd/$_cache_hash";

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
					header('Last-Modified: '.$gmdate_mod);
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
?>
