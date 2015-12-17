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
 * @param $input
 * @return string
 */
function html_escape($input) {
	return htmlentities($input, ENT_QUOTES, "UTF-8");
}

/**
 * Make sure some data is safe to be used in integer context
 *
 * @param $input
 * @return int
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
 * @param $input
 * @return string
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
 * @param $input
 * @return string
 */
function sql_escape($input) {
	global $database;
	return $database->escape($input);
}


/**
 * Turn all manner of HTML / INI / JS / DB booleans into a PHP one
 *
 * @param $input
 * @return bool
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
 * @param $input
 * @return string
 */
function no_escape($input) {
	return $input;
}

/**
 * @param int $val
 * @param int|null $min
 * @param int|null $max
 * @return int
 */
function clamp($val, $min, $max) {
	if(!is_numeric($val) || (!is_null($min) && $val < $min)) {
		$val = $min;
	}
	if(!is_null($max) && $val > $max) {
		$val = $max;
	}
	if(!is_null($min) && !is_null($max)) {
		assert('$val >= $min && $val <= $max', "$min <= $val <= $max");
	}
	return $val;
}

/**
 * @param string $name
 * @param array $attrs
 * @param array $children
 * @return string
 */
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
 * @param $limit
 * @return int
 */
function parse_shorthand_int($limit) {
	if(is_numeric($limit)) {
		return (int)$limit;
	}

	if(preg_match('/^([\d\.]+)([gmk])?b?$/i', (string)$limit, $m)) {
		$value = $m[1];
		if (isset($m[2])) {
			switch(strtolower($m[2])) {
				/** @noinspection PhpMissingBreakStatementInspection */
				case 'g': $value *= 1024;  // fall through
				/** @noinspection PhpMissingBreakStatementInspection */
				case 'm': $value *= 1024;  // fall through
				/** @noinspection PhpMissingBreakStatementInspection */
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
 * @param $int
 * @return string
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
 * @param $date
 * @param bool $html
 * @return string
 */
function autodate($date, $html=true) {
	$cpu = date('c', strtotime($date));
	$hum = date('F j, Y; H:i', strtotime($date));
	return ($html ? "<time datetime='$cpu'>$hum</time>" : $hum);
}

/**
 * Check if a given string is a valid date-time. ( Format: yyyy-mm-dd hh:mm:ss )
 *
 * @param $dateTime
 * @return bool
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
 * @param $date
 * @return bool
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

function validate_input($inputs) {
	$outputs = array();

	foreach($inputs as $key => $validations) {
		$flags = explode(',', $validations);

		if(in_array('bool', $flags) && !isset($_POST[$key])) {
			$_POST[$key] = 'off';
		}

		if(in_array('optional', $flags)) {
			if(!isset($_POST[$key]) || trim($_POST[$key]) == "") {
				$outputs[$key] = null;
				continue;
			}
		}
		if(!isset($_POST[$key]) || trim($_POST[$key]) == "") {
			throw new InvalidInput("Input '$key' not set");
		}

		$value = trim($_POST[$key]);

		if(in_array('user_id', $flags)) {
			$id = int_escape($value);
			if(in_array('exists', $flags)) {
				if(is_null(User::by_id($id))) {
					throw new InvalidInput("User #$id does not exist");
				}
			}
			$outputs[$key] = $id;
		}
		else if(in_array('user_name', $flags)) {
			if(strlen($value) < 1) {
				throw new InvalidInput("Username must be at least 1 character");
			}
			else if(!preg_match('/^[a-zA-Z0-9-_]+$/', $value)) {
				throw new InvalidInput(
						"Username contains invalid characters. Allowed characters are ".
						"letters, numbers, dash, and underscore");
			}
			$outputs[$key] = $value;
		}
		else if(in_array('user_class', $flags)) {
			global $_shm_user_classes;
			if(!array_key_exists($value, $_shm_user_classes)) {
				throw new InvalidInput("Invalid user class: ".html_escape($value));
			}
			$outputs[$key] = $value;
		}
		else if(in_array('email', $flags)) {
			$outputs[$key] = trim($value);
		}
		else if(in_array('password', $flags)) {
			$outputs[$key] = $value;
		}
		else if(in_array('int', $flags)) {
			$value = trim($value);
			if(empty($value) || !is_numeric($value)) {
				throw new InvalidInput("Invalid int: ".html_escape($value));
			}
			$outputs[$key] = (int)$value;
		}
		else if(in_array('bool', $flags)) {
			$outputs[$key] = bool_escape($value);
		}
		else if(in_array('string', $flags)) {
			if(in_array('trim', $flags)) {
				$value = trim($value);
			}
			if(in_array('lower', $flags)) {
				$value = strtolower($value);
			}
			if(in_array('not-empty', $flags)) {
				throw new InvalidInput("$key must not be blank");
			}
			if(in_array('nullify', $flags)) {
				if(empty($value)) $value = null;
			}
			$outputs[$key] = $value;
		}
		else {
			throw new InvalidInput("Unknown validation '$validations'");
		}
	}

	return $outputs;
}

/**
 * Give a HTML string which shows an IP (if the user is allowed to see IPs),
 * and a link to ban that IP (if the user is allowed to ban IPs)
 *
 * FIXME: also check that IP ban ext is installed
 *
 * @param $ip
 * @param $ban_reason
 * @return string
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
 * @return bool
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
 * @return bool
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
 * @param null|string $page
 * @param null|string $query
 * @return string
 */
function make_link($page=null, $query=null) {
	global $config;

	if(is_null($page)) $page = $config->get_string('main_page');

	if(NICE_URLS || $config->get_bool('nice_urls', false)) {
		$base = str_replace('/'.basename($_SERVER["SCRIPT_FILENAME"]), "", $_SERVER["PHP_SELF"]);
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
 * Take the current URL and modify some parameters
 *
 * @param $changes
 * @return string
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
		$base = _get_query();
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
 * @param string $link
 * @return string
 */
function make_http(/*string*/ $link) {
	if(strpos($link, "://") > 0) {
		return $link;
	}

	if(strlen($link) > 0 && $link[0] != '/') {
		$link = get_base_href() . '/' . $link;
	}

	$protocol = is_https_enabled() ? "https://" : "http://";
	$link = $protocol . $_SERVER["HTTP_HOST"] . $link;
	$link = str_replace("/./", "/", $link);

	return $link;
}

/**
 * Make a form tag with relevant auth token and stuff
 *
 * @param string $target
 * @param string $method
 * @param bool $multipart
 * @param string $form_id
 * @param string $onsubmit
 *
 * @return string
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

/**
 * @param string $file The filename
 * @return string
 */
function mtimefile($file) {
	$data_href = get_base_href();
	$mtime = filemtime($file);
	return "$data_href/$file?$mtime";
}

/**
 * Return the current theme as a string
 *
 * @return string
 */
function get_theme() {
	global $config;
	$theme = $config->get_string("theme", "default");
	if(!file_exists("themes/$theme")) $theme = "default";
	return $theme;
}

/**
 * Like glob, with support for matching very long patterns with braces.
 *
 * @param string $pattern
 * @return array
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

/**
 * @return string
 */
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
			//$securimg = new Securimage();
			$base = get_base_href();
			$captcha = "<br/><img src='$base/lib/securimage/securimage_show.php?sid=". md5(uniqid(time())) ."'>".
				"<br/>CAPTCHA: <input type='text' name='code' value='' />";
		}
	}
	return $captcha;
}

/**
 * @return bool
 */
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
 * Check if HTTPS is enabled for the server.
 *
 * @return bool True if HTTPS is enabled
 */
function is_https_enabled() {
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
}

/**
 * Get MIME type for file
 *
 * The contents of this function are taken from the __getMimeType() function
 * from the "Amazon S3 PHP class" which is Copyright (c) 2008, Donovan Schönknecht
 * and released under the 'Simplified BSD License'.
 *
 * @param string &$file File path
 * @param string $ext
 * @param bool $list
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

/**
 * @param string $mime_type
 * @return bool|string
 */
function getExtension ($mime_type){
	if(empty($mime_type)){
		return false;
	}

	$extensions = getMimeType(null, null, true);
	$ext = array_search($mime_type, $extensions);
	return ($ext ? $ext : false);
}

/**
 * Compare two Block objects, used to sort them before being displayed
 *
 * @param Block $a
 * @param Block $b
 * @return int
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
 * @return int
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
 * @param Config $config
 * @return string
 */
function get_session_ip(Config $config) {
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
 *
 * @param string $text
 * @param string $type
 */
function flash_message(/*string*/ $text, /*string*/ $type="info") {
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
 * Figure out the path to the shimmie install directory.
 *
 * eg if shimmie is visible at http://foo.com/gallery, this
 * function should return /gallery
 *
 * PHP really, really sucks.
 *
 * @return string
 */
function get_base_href() {
	if(defined("BASE_HREF")) return BASE_HREF;
	$possible_vars = array('SCRIPT_NAME', 'PHP_SELF', 'PATH_INFO', 'ORIG_PATH_INFO');
	$ok_var = null;
	foreach($possible_vars as $var) {
		if(isset($_SERVER[$var]) && substr($_SERVER[$var], -4) === '.php') {
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
 * A shorthand way to send a TextFormattingEvent and get the results.
 *
 * @param string $string
 * @return string
 */
function format_text(/*string*/ $string) {
	$tfe = new TextFormattingEvent($string);
	send_event($tfe);
	return $tfe->formatted;
}

/**
 * @param string $base
 * @param string $hash
 * @param bool $create
 * @return string
 */
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

/**
 * @param string $filename
 * @return string
 */
function data_path($filename) {
	$filename = "data/" . $filename;
	if(!file_exists(dirname($filename))) mkdir(dirname($filename), 0755, true);
	return $filename;
}

/**
 * @param string $url
 * @param string $mfile
 * @return array|bool
 */
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
		$fp_in = @fopen($url, "r");
		$fp_out = fopen($mfile, "w");
		if(!$fp_in || !$fp_out) {
			return false;
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

/**
 * HTTP Headers can sometimes be lowercase which will cause issues.
 * In cases like these, we need to make sure to check for them if the camelcase version does not exist.
 * 
 * @param array $headers
 * @param mixed $name
 * @return mixed
 */
function findHeader ($headers, $name) {
	if (!is_array($headers)) {
		return false;
	}
	
	$header = false;

	if(array_key_exists($name, $headers)) {
		$header = $headers[$name];
	} else {
		$headers = array_change_key_case($headers); // convert all to lower case.
		$lc_name = strtolower($name);
		
		if(array_key_exists($lc_name, $headers)) {
			$header = $headers[$lc_name];
		}
	}

	return $header;
}

/**
 * Get the active contents of a .php file
 *
 * @param string $fname
 * @return string|null
 */
function manual_include($fname) {
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
 *
 * @param string $section
 * @param int $priority
 * @param string $message
 * @param bool|string $flash
 * @param array $args
 */
function log_msg(/*string*/ $section, /*int*/ $priority, /*string*/ $message, $flash=false, $args=array()) {
	send_event(new LogEvent($section, $priority, $message, $args));
	$threshold = defined("CLI_LOG_LEVEL") ? CLI_LOG_LEVEL : 0;

	if((PHP_SAPI === 'cli') && ($priority >= $threshold)) {
		print date("c")." $section: $message\n";
	}
	if($flash === true) {
		flash_message($message);
	}
	else if(is_string($flash)) {
		flash_message($flash);
	}
}

// More shorthand ways of logging
function log_debug(   /*string*/ $section, /*string*/ $message, $flash=false, $args=array()) {log_msg($section, SCORE_LOG_DEBUG, $message, $flash, $args);}
function log_info(    /*string*/ $section, /*string*/ $message, $flash=false, $args=array()) {log_msg($section, SCORE_LOG_INFO, $message, $flash, $args);}
function log_warning( /*string*/ $section, /*string*/ $message, $flash=false, $args=array()) {log_msg($section, SCORE_LOG_WARNING, $message, $flash, $args);}
function log_error(   /*string*/ $section, /*string*/ $message, $flash=false, $args=array()) {log_msg($section, SCORE_LOG_ERROR, $message, $flash, $args);}
function log_critical(/*string*/ $section, /*string*/ $message, $flash=false, $args=array()) {log_msg($section, SCORE_LOG_CRITICAL, $message, $flash, $args);}


/**
 * Get a unique ID for this request, useful for grouping log messages.
 *
 * @return null|string
 */
function get_request_id() {
	static $request_id = null;
	if(!$request_id) {
		// not completely trustworthy, as a user can spoof this
		if(@$_SERVER['HTTP_X_VARNISH']) {
			$request_id = $_SERVER['HTTP_X_VARNISH'];
		}
		else {
			$request_id = "P" . uniqid();
		}
	}
	return $request_id;
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Things which should be in the core API                                    *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Remove an item from an array
 *
 * @param $array
 * @param $to_remove
 * @return array
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
 * @param $array
 * @param $element
 * @return array
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
 * @param $array
 * @return array
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
 * @param $IP
 * @param $CIDR
 * @return bool
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
 *
 * @param string $f
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
 * Copy an entire file hierarchy
 *
 * from a comment on http://uk.php.net/copy
 *
 * @param string $source
 * @param string $target
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
 * Return a list of all the regular files in a directory and subdirectories
 *
 * @param string $base
 * @param string $_sub_dir
 * @return array file list
 */
function list_files(/*string*/ $base, $_sub_dir="") {
	assert(is_dir($base));

	$file_list = array();

	$files = array();
	$dir = opendir("$base/$_sub_dir");
	while($f = readdir($dir)) {
		$files[] = $f;
	}
	closedir($dir);
	sort($files);

	foreach($files as $filename) {
		$full_path = "$base/$_sub_dir/$filename";

		if(is_link($full_path)) {
			// ignore
		}
		else if(is_dir($full_path)) {
			if(!($filename == "." || $filename == "..")) {
				//subdirectory found
				$file_list = array_merge(
					$file_list,
					list_files($base, "$_sub_dir/$filename")
				);
			}
		}
		else {
			$full_path = str_replace("//", "/", $full_path);
			$file_list[] = $full_path;
		}
	}

	return $file_list;
}


function path_to_tags($path) {
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
* Event API                                                                 *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/** @private */
global $_shm_event_listeners;
$_shm_event_listeners = array();

function _load_event_listeners() {
	global $_shm_event_listeners;

	ctx_log_start("Loading extensions");

	$cache_path = data_path("cache/shm_event_listeners.php");
	if(COMPILE_ELS && file_exists($cache_path)) {
		require_once($cache_path);
	}
	else {
		_set_event_listeners();

		if(COMPILE_ELS) {
			_dump_event_listeners($_shm_event_listeners, $cache_path);
		}
	}

	ctx_log_endok();
}

function _set_event_listeners() {
	global $_shm_event_listeners;
	$_shm_event_listeners = array();

	foreach(get_declared_classes() as $class) {
		$rclass = new ReflectionClass($class);
		if($rclass->isAbstract()) {
			// don't do anything
		}
		elseif(is_subclass_of($class, "Extension")) {
			/** @var Extension $extension */
			$extension = new $class();
			$extension->i_am($extension);

			// skip extensions which don't support our current database
			if(!$extension->is_live()) continue;

			foreach(get_class_methods($extension) as $method) {
				if(substr($method, 0, 2) == "on") {
					$event = substr($method, 2) . "Event";
					$pos = $extension->get_priority() * 100;
					while(isset($_shm_event_listeners[$event][$pos])) {
						$pos += 1;
					}
					$_shm_event_listeners[$event][$pos] = $extension;
				}
			}
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

	$p .= "\$_shm_event_listeners = array(\n";
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

/**
 * @param $ext_name string
 * @return bool
 */
function ext_is_live($ext_name) {
	if (class_exists($ext_name)) {
		/** @var Extension $ext */
		$ext = new $ext_name();
		return $ext->is_live();
	}
	return false;
}


/** @private */
global $_shm_event_count;
$_shm_event_count = 0;

/**
 * Send an event to all registered Extensions.
 *
 * @param Event $event
 */
function send_event(Event $event) {
	global $_shm_event_listeners, $_shm_event_count;
	if(!isset($_shm_event_listeners[get_class($event)])) return;
	$method_name = "on".str_replace("Event", "", get_class($event));

	// send_event() is performance sensitive, and with the number
	// of times context gets called the time starts to add up
	$ctx = constant('CONTEXT');

	if($ctx) ctx_log_start(get_class($event));
	// SHIT: http://bugs.php.net/bug.php?id=35106
	$my_event_listeners = $_shm_event_listeners[get_class($event)];
	ksort($my_event_listeners);
	foreach($my_event_listeners as $listener) {
		if($ctx) ctx_log_start(get_class($listener));
		if(method_exists($listener, $method_name)) {
			$listener->$method_name($event);
		}
		if($ctx) ctx_log_endok();
	}
	$_shm_event_count++;
	if($ctx) ctx_log_endok();
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
 *
 * @return string debug info to add to the page.
 */
function get_debug_info() {
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
	$min_version = "5.4.8";
	if(version_compare(PHP_VERSION, $min_version) == -1) {
		print "
Currently SCore Engine doesn't support versions of PHP lower than $min_version --
if your web host is running an older version, they are dangerously out of
date and you should plan on moving elsewhere.
";
		exit;
	}
}

function _sanitise_environment() {
	if(TIMEZONE) {
		date_default_timezone_set(TIMEZONE);
	}

	if(DEBUG) {
		error_reporting(E_ALL);
		assert_options(ASSERT_ACTIVE, 1);
		assert_options(ASSERT_BAIL, 1);
		assert_options(ASSERT_WARNING, 0);
		assert_options(ASSERT_QUIET_EVAL, 1);
		assert_options(ASSERT_CALLBACK, 'score_assert_handler');
	}

	if(CONTEXT) {
		ctx_set_log(CONTEXT);
	}

	if(COVERAGE) {
		_start_coverage();
		register_shutdown_function("_end_coverage");
	}

	ob_start();

	if(PHP_SAPI === 'cli') {
		if(isset($_SERVER['REMOTE_ADDR'])) {
			die("CLI with remote addr? Confused, not taking the risk.");
		}
		$_SERVER['REMOTE_ADDR'] = "0.0.0.0";
		$_SERVER['HTTP_HOST'] = "<cli command>";
	}
}


/**
 * @param string $_theme
 * @return array
 */
function _get_themelet_files($_theme) {
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
 * @param Exception $e
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
 *
 * @param string $str
 * @return string
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

/**
 * @return User
 */
function _get_user() {
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

function _get_query() {
	return @$_POST["q"]?:@$_GET["q"];
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

