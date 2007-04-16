<?php
function html_escape($input) {
	return htmlentities($input);
}

function int_escape($input) {
	return (int)$input;
}

function sql_escape($input) {
	global $database;
	return $database->db->Quote($input);
}

function make_link($page, $query=null) {
	global $config;
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

function bbcode2html($text) {
	$text = trim($text);
	$text = html_escape($text);
#	$text = preg_replace("/\[b\](.*?)\[\/b\]/s", "<b>\\1</b>", $text);
#	$text = preg_replace("/\[i\](.*?)\[\/i\]/s", "<i>\\1</i>", $text);
#	$text = preg_replace("/\[u\](.*?)\[\/u\]/s", "<u>\\1</u>", $text);
	$text = str_replace("\n", "\n<br>", $text);
	return $text;
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





# $db is the connection object
function CountExecs($db, $sql, $inputarray) {
	global $_execs;
#	$fp = fopen("sql.log", "a");
#	fwrite($fp, preg_replace('/\s+/msi', ' ', $sql)."\n");
#	fclose($fp);
	if (!is_array($inputarray)) $_execs++;
	# handle 2-dimensional input arrays
	else if (is_array(reset($inputarray))) $_execs += sizeof($inputarray);
	else $_execs++;
	# in PHP4.4 and PHP5, we need to return a value by reference
	$null = null; return $null;
}


// internal things

$_event_listeners = array();

function add_event_listener($block, $pos=50) {
	global $_event_listeners;
	while(isset($_event_listeners[$pos])) {
		$pos++;
	}
	$_event_listeners[$pos] = $block;
}

function send_event($event) {
	global $_event_listeners;
	foreach($_event_listeners as $listener) {
		$listener->receive_event($event);
	}
}


function _get_query_parts() {
	if(isset($_GET["q"])) {
		$path = $_GET["q"];
	}
	else if(isset($_SERVER["PATH_INFO"])) {
		$path = $_SERVER["PATH_INFO"];
	}
	else {
		$path = "index/1";
	}
	
	while(strlen($path) > 0 && $path[0] == '/') {
		$path = substr($path, 1);
	}

	return split('/', $path);
}
function get_page_request() {
	$args = _get_query_parts();

	if(count($args) == 0) {
		$page = "index";
		$args = array();
	}
	else if(count($args) == 1) {
		$page = (strlen($args[0]) > 0 ? $args[0] : "index");
		$args = array();
	}
	else {
		$page = $args[0];
		$args = array_slice($args, 1);
	}
	
	return new PageRequestEvent($page, $args);
}

function get_user() {
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
		$user = $database->get_user($config->get_int("anon_id"));
	}
	return $user;
}

?>
