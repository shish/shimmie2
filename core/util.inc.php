<?php
require_once "lib/recaptchalib.php";
require_once "lib/securimage/securimage.php";
require_once "lib/context.php";

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
	/*
		Shish: I have a feeling that these three lines are important, possibly for searching for tags with slashes in them like fate/stay_night
		green-ponies: indeed~

	$input = str_replace('^', '^^', $input);
	$input = str_replace('/', '^s', $input);
	$input = str_replace('\\', '^b', $input);

	/* The function idn_to_ascii is used to support Unicode domains / URLs as well.
	   See here for more:  http://php.net/manual/en/function.filter-var.php
	   However, it is only supported by PHP version 5.3 and up

	if (function_exists('idn_to_ascii')) {
			return filter_var(idn_to_ascii($input), FILTER_SANITIZE_URL);
	} else {
			return filter_var($input, FILTER_SANITIZE_URL);
	}
	*/
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
	return $database->escape($input);
}


/**
 * Turn all manner of HTML / INI / JS / DB booleans into a PHP one
 *
 * @retval boolean
 */
function bool_escape($input) {
	/*
	 Sometimes, I don't like PHP -- this, is one of those times...
	  "a boolean FALSE is not considered a valid boolean value by this function."
	 Yay for Got'chas!	
	 http://php.net/manual/en/filter.filters.validate.php
	*/
	if (is_bool($input)) {
		return $input;
	} else if (is_numeric($input)) {
		return ($input === 1);
	} else {
		$value = filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		if (!is_null($value)) {
			return $value;
		} else {
			$input = strtolower( trim($input) );
			return (
				$input === "y" ||
				$input === "yes" ||
				$input === "t" ||
				$input === "true" ||
				$input === "on" ||
				$input === "1"
			);
		}
	}
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

function xml_tag($name, $attrs=array(), $children=array()) {
	$xml = "<$name ";
	foreach($attrs as $k => $v) {
		$xv = str_replace('&#039;', '&apos;', htmlspecialchars($v, ENT_QUOTES));
		$xml .= "$k=\"$xv\" ";
	}
	if(count($children) > 0) {
		$xml .= ">\n";
		foreach($children as $child) {
			$xml .= xml_tag($child);
		}
		$xml .= "</$name>\n";
	}
	else {
		$xml .= "/>\n";
	}
	return $xml;
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
		$base = str_replace('/'.basename($_SERVER["SCRIPT_FILENAME"]), "", $full);
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

function get_theme() {
	global $config;
	$theme = $config->get_string("theme", "default");
	if(!file_exists("themes/$theme")) $theme = "default";
	return $theme;
}

/*
 * like glob, with support for matching very long patterns with braces
 */
function zglob($pattern) {
	$results = array();
	if(preg_match('/(.*)\{(.*)\}(.*)/', $pattern, $matches)) {
		$braced = explode(",", $matches[2]);
		foreach($braced as $b) {
			$sub_pattern = $matches[1].$b.$matches[3];
			$results = array_merge($results, zglob($sub_pattern));
		}
		return $results;
	}
	else {
		$r = glob($pattern);
		if($r) return $r;
		else return array();
	}
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* CAPTCHA abstraction                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function captcha_get_html() {
	global $config, $user;

	if(DEBUG && ip_in_range($_SERVER['REMOTE_ADDR'], "127.0.0.0/8")) return "";

	$captcha = "";
	if($user->is_anonymous() && $config->get_bool("comment_captcha")) {
		$r_publickey = $config->get_string("api_recaptcha_pubkey");
		if(!empty($r_publickey)) {
			$captcha = recaptcha_get_html($r_publickey);
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
		$r_privatekey = $config->get_string('api_recaptcha_privkey');
		if(!empty($r_privatekey)) {
			$resp = recaptcha_check_answer(
				$r_privatekey,
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
* Get MIME type for file
*
* The contents of this function are taken from the __getMimeType() function
* from the "Amazon S3 PHP class" which is Copyright (c) 2008, Donovan Schönknecht
* and released under the 'Simplified BSD License'.
*
* @internal Used to get mime types
* @param string &$file File path
* @return string
*/
function getMimeType($file, $ext="", $list=false) {

	// Static extension lookup
	$ext = strtolower($ext);
	static $exts = array(
		'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png',
		'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'ico' => 'image/x-icon',
		'swf' => 'application/x-shockwave-flash', 'video/x-flv' => 'flv',
		'svg' => 'image/svg+xml', 'pdf' => 'application/pdf',
		'zip' => 'application/zip', 'gz' => 'application/x-gzip',
		'tar' => 'application/x-tar', 'bz' => 'application/x-bzip',
		'bz2' => 'application/x-bzip2', 'txt' => 'text/plain',
		'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html',
		'css' => 'text/css', 'js' => 'text/javascript',
		'xml' => 'text/xml', 'xsl' => 'application/xsl+xml',
		'ogg' => 'application/ogg', 'mp3' => 'audio/mpeg', 'wav' => 'audio/x-wav',
		'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg',
		'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'text/x-php',
		'mp4' => 'video/mp4', 'ogv' => 'video/ogg', 'webm' => 'video/webm'
	);

	if ($list == true){ return $exts; }

	if (isset($exts[$ext])) { return $exts[$ext]; }

	$type = false;
	// Fileinfo documentation says fileinfo_open() will use the
	// MAGIC env var for the magic file
	if (extension_loaded('fileinfo') && isset($_ENV['MAGIC']) &&
	($finfo = finfo_open(FILEINFO_MIME, $_ENV['MAGIC'])) !== false)
	{
		if (($type = finfo_file($finfo, $file)) !== false)
		{
			// Remove the charset and grab the last content-type
			$type = explode(' ', str_replace('; charset=', ';charset=', $type));
			$type = array_pop($type);
			$type = explode(';', $type);
			$type = trim(array_shift($type));
		}
		finfo_close($finfo);

	// If anyone is still using mime_content_type()
	} elseif (function_exists('mime_content_type'))
		$type = trim(mime_content_type($file));

	if ($type !== false && strlen($type) > 0) return $type;
	
	return 'application/octet-stream';
}


function getExtension ($mime_type){
	if(empty($mime_type)){
		return false;
	}

	$extensions = getMimeType(null, null, true);
	$ext = array_search($mime_type, $extensions);
	return ($ext ?: false);
}

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
function is_cli() {
	return (PHP_SAPI === 'cli');
}


$_execs = 0;
/**
 * $db is the connection object
 *
 * @private
 */
function _count_execs($db, $sql, $inputarray) {
	global $_execs;
	if (defined(DEBUG_SQL) && (DEBUG_SQL === true || (is_null(DEBUG_SQL) && @$_GET['DEBUG_SQL']))) {
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
	$full_name = COOKIE_PREFIX."_".$name;
	setcookie($full_name, $value, $time, $path);
}

/**
 * Set (or extend) a flash-message cookie
 *
 * This can optionally be done at the same time as saving a log message with log_*()
 *
 * Generally one should flash a message in onPageRequest and log a message wherever
 * the action actually takes place (eg onWhateverElse) - but much of the time, actions
 * are taken from within onPageRequest...
 */
function flash_message(/*string*/ $text, /*string*/ $type="info") {
	$current = get_prefixed_cookie("flash_message");
	if($current) {
		$text = $current . "\n" . $text;
	}
	# the message should be viewed pretty much immediately,
	# so 60s timeout should be more than enough
	set_prefixed_cookie("flash_message", $text, time()+60, "/");
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
	$dir = str_replace("\\", "/", $dir);
	$dir = str_replace("//", "/", $dir);
	$dir = rtrim($dir, "/");
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

function data_path($filename) {
	$filename = "data/" . $filename;
	if(!file_exists(dirname($filename))) mkdir(dirname($filename), 0755, true);
	return $filename;
}

function transload($url, $mfile) {
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

		$headers = http_parse_headers(implode("\n", $http_response_header));

		return $headers;
	}

	return false;
}

if (!function_exists('http_parse_headers')) { #http://www.php.net/manual/en/function.http-parse-headers.php#112917
	function http_parse_headers ($raw_headers){
		$headers = array(); // $headers = [];

		foreach (explode("\n", $raw_headers) as $i => $h) {
			$h = explode(':', $h, 2);

			if (isset($h[1])){
				if(!isset($headers[$h[0]])){
					$headers[$h[0]] = trim($h[1]);
				}else if(is_array($headers[$h[0]])){
					$tmp = array_merge($headers[$h[0]],array(trim($h[1])));
					$headers[$h[0]] = $tmp;
				}else{
					$tmp = array_merge(array($headers[$h[0]]),array(trim($h[1])));
					$headers[$h[0]] = $tmp;
				}
			}
		}
		return $headers;
	}
}

$_included = array();
/**
 * Get the active contents of a .php file
 */
function manual_include($fname) {
	if(!file_exists($fname)) return;

	global $_included;
	if(in_array($fname, $_included)) return;
	$_included[] = $fname;

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

	// wibble the defines for HipHop's sake
	$text = str_replace('function _d(', '// function _messed_d(', $text);
	$text = preg_replace('/_d\("(.*)", (.*)\);/', 'if(!defined("$1")) define("$1", $2);', $text);

	return $text;
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
 *
 * When parsing a user request, a flash message should give info to the user
 * When taking action, a log event should be stored by the server
 * Quite often, both of these happen at once, hence log_*() having $flash
 *
 * $flash = null (default) - log to server only, no flash message
 * $flash = true           - show the message to the user as well
 * $flash = "some string"  - log the message, flash the string
 */
function log_msg(/*string*/ $section, /*int*/ $priority, /*string*/ $message, $flash=null, $args=array()) {
	send_event(new LogEvent($section, $priority, $message, $args));
	$threshold = defined("CLI_LOG_LEVEL") ? CLI_LOG_LEVEL : 0;
	if(is_cli() && ($priority >= $threshold)) {
		print date("c")." $section: $message\n";
	}
	if($flash === True) {
		flash_message($message);
	}
	else if(!is_null($flash)) {
		flash_message($flash);
	}
}

// More shorthand ways of logging
function log_debug(   /*string*/ $section, /*string*/ $message, $flash=null, $args=array()) {log_msg($section, SCORE_LOG_DEBUG, $message, $flash, $args);}
function log_info(    /*string*/ $section, /*string*/ $message, $flash=null, $args=array()) {log_msg($section, SCORE_LOG_INFO, $message, $flash, $args);}
function log_warning( /*string*/ $section, /*string*/ $message, $flash=null, $args=array()) {log_msg($section, SCORE_LOG_WARNING, $message, $flash, $args);}
function log_error(   /*string*/ $section, /*string*/ $message, $flash=null, $args=array()) {log_msg($section, SCORE_LOG_ERROR, $message, $flash, $args);}
function log_critical(/*string*/ $section, /*string*/ $message, $flash=null, $args=array()) {log_msg($section, SCORE_LOG_CRITICAL, $message, $flash, $args);}

/**
 * Get a unique ID for this request, useful for grouping log messages
 */
$_request_id = null;
function get_request_id() {
	global $_request_id;
	if(!$_request_id) {
		// not completely trustworthy, as a user can spoof this
		if(@$_SERVER['HTTP_X_VARNISH']) {
			$_request_id = $_SERVER['HTTP_X_VARNISH'];
		}
		else {
			$_request_id = "P" . uniqid();
		}
	}
	return $_request_id;
}


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
	$pos *= 100;
	foreach($events as $event) {
		while(isset($_event_listeners[$event][$pos])) {
			$pos += 1;
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

	if(CONTEXT) {
		ctx_set_log(CONTEXT);
		ctx_log_start(@$_SERVER["REQUEST_URI"], true, true);
	}

	if(COVERAGE) {
		_start_coverage();
		register_shutdown_function("_end_coverage");
	}

	assert_options(ASSERT_ACTIVE, 1);
	assert_options(ASSERT_BAIL, 1);

	ob_start();

	if(get_magic_quotes_gpc()) {
		$_GET = _stripslashes_r($_GET);
		$_POST = _stripslashes_r($_POST);
		$_COOKIE = _stripslashes_r($_COOKIE);
	}

	if(is_cli()) {
		if(isset($_SERVER['REMOTE_ADDR'])) {
			die("CLI with remote addr? Confused, not taking the risk.");
		}
		$_SERVER['REMOTE_ADDR'] = "0.0.0.0";
		$_SERVER['HTTP_HOST'] = "<cli command>";
	}
}

function _get_themelet_files($_theme) {
	if(file_exists('themes/'.$_theme.'/custompage.class.php')) $base_themelets[] = 'themes/'.$_theme.'/custompage.class.php';
	$base_themelets[] = 'themes/'.$_theme.'/layout.class.php';
	$base_themelets[] = 'themes/'.$_theme.'/themelet.class.php';

	$ext_themelets = zglob("ext/{".ENABLED_EXTS."}/theme.php");
	$custom_themelets = zglob('themes/'.$_theme.'/{'.ENABLED_EXTS.'}.theme.php');

	return array_merge($base_themelets, $ext_themelets, $custom_themelets);
}

function _set_event_listeners($classes) {
	global $_event_listeners;
	$_event_listeners = array();

	foreach($classes as $class) {
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
}

function _dump_event_listeners($event_listeners, $path) {
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
	foreach($event_listeners as $event => $listeners) {
		$p .= "\t'$event' => array(\n";
		foreach($listeners as $id => $listener) {
			$p .= "\t\t$id => \$".get_class($listener).",\n";
		}
		$p .= "\t),\n";
	}
	$p .= ");\n";

	$p .= "?".">";
	file_put_contents($path, $p);
}

function _load_extensions() {
	global $_event_listeners;

	ctx_log_start("Loading extensions");

	if(COMPILE_ELS && file_exists("data/cache/event_listeners.php")) {
		require_once("data/cache/event_listeners.php");
	}
	else {
		_set_event_listeners(get_declared_classes());

		if(COMPILE_ELS) {
			_dump_event_listeners($_event_listeners, data_path("cache/event_listeners.php"));
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
		<p><b>Version:</b> '.$version.'
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
