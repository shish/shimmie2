<?php
/**
 * @package SCore
 */

/**
 * Generic parent class for all events.
 *
 * An event is anything that can be passed around via send_event($blah)
 */
abstract class Event {
	public function __construct() {}
}


/**
 * A wake-up call for extensions. Upon recieving an InitExtEvent an extension
 * should check that it's database tables are there and install them if not,
 * and set any defaults with Config::set_default_int() and such.
 */
class InitExtEvent extends Event {}


/**
 * A signal that a page has been requested.
 *
 * User requests /view/42 -> an event is generated with $args = array("view",
 * "42"); when an event handler asks $event->page_matches("view"), it returns
 * true and ignores the matched part, such that $event->count_args() = 1 and
 * $event->get_arg(0) = "42"
 */
class PageRequestEvent extends Event {
	var $args;
	var $arg_count;
	var $part_count;

	public function __construct($args) {
		$this->args = $args;
		$this->arg_count = count($args);
	}

	/**
	 * Test if the requested path matches a given pattern.
	 *
	 * If it matches, store the remaining path elements in $args
	 */
	public function page_matches($name) {
		$parts = explode("/", $name);
		$this->part_count = count($parts);

		if($this->part_count > $this->arg_count) {
			return false;
		}

		for($i=0; $i<$this->part_count; $i++) {
			if($parts[$i] != $this->args[$i]) {
				return false;
			}
		}

		return true;
	}

	public function get_arg($n) {
		$offset = $this->part_count + $n;
		if($offset >= 0 && $offset < $this->arg_count) {
			return $this->args[$offset];
		}
		else {
			return null;
		}
	}

	public function count_args() {
		return $this->arg_count - $this->part_count;
	}
}


/**
 * A signal that some text needs formatting, the event carries
 * both the text and the result
 */
class TextFormattingEvent extends Event {
	/**
	 * For reference
	 */
	var $original;

	/**
	 * with formatting applied
	 */
	var $formatted;

	/**
	 * with formatting removed
	 */
	var $stripped;

	public function __construct($text) {
		$h_text = html_escape(trim($text));
		$this->original  = $h_text;
		$this->formatted = $h_text;
		$this->stripped  = $h_text;
	}
}


/**
 * A signal that something needs logging
 */
class LogEvent extends Event {
	/**
	 * a category, normally the extension name
	 *
	 * @var string
	 */
	var $section;

	/**
	 * See python...
	 *
	 * @var int
	 */
	var $priority = 0;

	/**
	 * Free text to be logged
	 *
	 * @var text
	 */
	var $message;

	/**
	 * The time that the event was created
	 *
	 * @var int
	 */
	var $time;

	public function __construct($section, $priority, $message) {
		$this->section = $section;
		$this->priority = $priority;
		$this->message = $message;
		$this->time = time();

		// this should be an extension
		if(defined("X-HALFASSED-LOGGING")) {
			global $user;
			$ftime = date("Y-m-d H:i:s", $this->time);
			$username = $user->name;
			$ip = $_SERVER['REMOTE_ADDR'];
			$fp = fopen("shimmie.log", "a");
			fputs($fp, "$ftime\t$section/$priority\t$username/$ip\t$message\n");
			fclose($fp);
		}
	}
}
?>
