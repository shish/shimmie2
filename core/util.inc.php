<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Input / Output Sanitising                                                 *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function html_escape($input) {
	return htmlentities($input, ENT_QUOTES, "UTF-8");
}

function int_escape($input) {
	return (int)$input;
}

function url_escape($input) {
	$input = str_replace('/', '//', $input);
	$input = rawurlencode($input);
	$input = str_replace('%2F', '/', $input);
	return $input;
}

function sql_escape($input) {
	global $database;
	return $database->db->Quote($input);
}

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

function tag_explode($tags) {
	if(is_string($tags)) {
		$tags = explode(' ', $tags);
	}
	else if(is_array($tags)) {
		// do nothing
	}
	else {
		die("tag_explode only takes strings or arrays");
	}

	$tags = array_map("trim", $tags);

	$tag_array = array();
	foreach($tags as $tag) {
		if(is_string($tag) && strlen($tag) > 0) {
			$tag_array[] = $tag;
		}
	}

	if(count($tag_array) == 0) {
		$tag_array = array("tagme");
	}

	return $tag_array;
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* HTML Generation                                                           *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function make_link($page=null, $query=null) {
	global $config;

	if(is_null($page)) $page = $config->get_string('main_page');

	$base = $config->get_string('base_href');

	if(is_null($query)) {
		return "$base/$page";
	}
	else {
		if(strpos($base, "?")) {
			return "$base/$page&$query";
		}
		else {
			return "$base/$page?$query";
		}
	}
}

function build_thumb_html($image, $query=null) {
	global $config;
	$h_view_link = make_link("post/view/{$image->id}", $query);
	$h_tip = html_escape($image->get_tooltip());
	$h_thumb_link = $image->get_thumb_link();
	$tsize = get_thumbnail_size($image->width, $image->height);
	return "<a href='$h_view_link'><img title='$h_tip' alt='$h_tip'
			width='{$tsize[0]}' height='{$tsize[1]}' src='$h_thumb_link' /></a>\n";
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Misc                                                                      *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function get_thumbnail_size($orig_width, $orig_height) {
	global $config;

	if($orig_width == 0) $orig_width = 192;
	if($orig_height == 0) $orig_height = 192;

	$max_width  = $config->get_int('thumb_width');
	$max_height = $config->get_int('thumb_height');

	$xscale = ($max_height / $orig_height);
	$yscale = ($max_width / $orig_width);
	$scale = ($xscale < $yscale) ? $xscale : $yscale;

	if($scale > 1 && $config->get_bool('thumb_upscale')) {
		return array((int)$orig_width, (int)$orig_height);
	}
	else {
		return array((int)($orig_width*$scale), (int)($orig_height*$scale));
	}
}

# $db is the connection object
function _count_execs($db, $sql, $inputarray) {
	global $_execs;
	if(DEBUG) {
		$fp = fopen("sql.log", "a");
		fwrite($fp, preg_replace('/\s+/msi', ' ', $sql)."\n");
		fclose($fp);
	}
	if (!is_array($inputarray)) $_execs++;
	# handle 2-dimensional input arrays
	else if (is_array(reset($inputarray))) $_execs += sizeof($inputarray);
	else $_execs++;
	# in PHP4.4 and PHP5, we need to return a value by reference
	$null = null; return $null;
}

function get_theme_object($file, $class) {
	global $config;
	$theme = $config->get_string("theme", "default");
	if(file_exists("themes/$theme/$file.theme.php")) {
		require_once "themes/$theme/$file.theme.php";
		return new $class();
	}
	else {
		require_once "ext/$file/theme.php";
		return new $class();
	}
}

function blockcmp($a, $b) {
	if($a->position == $b->position) {
		return 0;
	}
	else {
		return ($a->position > $b->position);
	}
}

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

function get_base_href() {
	$dir = dirname($_SERVER['SCRIPT_NAME']);
	if($dir == "/") $dir = "";
	return $dir;
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Debugging functions                                                       *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function get_debug_info() {
	global $config;
	
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
	global $_execs;
	$debug = "<br>Took $i_utime + $i_stime seconds and {$i_mem}MB of RAM";
	$debug .= "; Used $i_files files and $_execs queries";

	return $debug;
}

function print_obj($array,$title="Object Information") { 
	global $user, $page;
	if(DEBUG && isset($_GET['debug']) && $user->is_admin()) { 
		//   big test: 
		//      $debug_active to be able to kill the function from the file system. 
		//      Look for ?debug (GET type data) to prevent the debug from appearing to regular browsing. 
		//      Finally an admin check, because variables may contain sensitive data. 
		//   once all that is cleared, make a block: 
		$page->add_block( 
				new Block( 
					$title, 
					str_replace("    ", 
						"<span style='opacity:0; -moz-opacity:0; alpha:0;'>__</span>", 
						str_replace("\n","<br/>",html_escape(print_r($array,true)))), 
					"main",1000 
				) 
		); 
	} 
	// print_r is called with the return option (not echo-style) to get a string 
	// output to work with. 

	// Then two str_replaces turn newlines into <br/> tags and indent spaces into 
	// rendered underscores (which are then hidden.) 

	// Finally the entire thing is packaged into a block and mailed to the main 
	// section at the bottom of the pile. 
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


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Things which should be in the core API                                    *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

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

function array_add($array, $element) { 
	$array[] = $element; 
	$array = array_unique($array); 
	return $array; 
} 


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Event API                                                                 *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

$_event_listeners = array();

function add_event_listener($extension, $pos=50) {
	global $_event_listeners;
	while(isset($_event_listeners[$pos])) {
		$pos++;
	}
	$_event_listeners[$pos] = $extension;
}

function send_event($event) {
	global $_event_listeners;
	$my_event_listeners = $_event_listeners; // http://bugs.php.net/bug.php?id=35106
	ksort($my_event_listeners);
	foreach($my_event_listeners as $listener) {
		$listener->receive_event($event);
		if($event->vetoed) break;
	}
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Request initialisation stuff                                              *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

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

	/*
	 * Split post/list/fate//stay_night/1
	 * into post list fate/stay_night 1
	 */
	/*
	$parts = array();
	$n = 0;
	$lastsplit = 0;
	while($n<=strlen($path)) {
		if(
				$n == strlen($path) ||
				(
					$path[$n] == '/' &&
					($n < strlen($path) && $path[$n+1] != '/')
					&& ($n > 0 && $path[$n-1] != '/')
				)
		) {
			$part = substr($path, $lastsplit, $n-$lastsplit);
			$part = str_replace('//', '/', $part);
			$parts[] = $part;
			$lastsplit = $n+1;
		}
		$n++;
	}
	*/
	$path = str_replace('/', ' ', $path);
	$path = str_replace('  ', '/', $path);
	$parts = split(' ', $path);

	return $parts;
}

function _get_page_request($page) {
	global $config;
	$args = _get_query_parts();

	if(count($args) == 0 || strlen($args[0]) == 0) {
		$parts = split('/', $config->get_string('front_page'));
		$page_name = array_shift($parts);
		$args = $parts;
	}
	else if(count($args) == 1) {
		$page_name = $args[0];
		$args = array();
	}
	else {
		$page_name = $args[0];
		$args = array_slice($args, 1);
	}
	
	return new PageRequestEvent($page_name, $args, $page);
}

function _get_user() {
	global $database;
	global $config;
	
	$user = null;
	if(isset($_COOKIE["shm_user"]) && isset($_COOKIE["shm_session"])) {
	    $tmp_user = $database->get_user_session($_COOKIE["shm_user"], $_COOKIE["shm_session"]);
		if(!is_null($tmp_user) && $tmp_user->is_enabled()) {
			$user = $tmp_user;
		}
		
	}
	if(is_null($user)) {
		$user = $database->get_user_by_id($config->get_int("anon_id", 0));
	}
	assert(!is_null($user));
	return $user;
}

?>
