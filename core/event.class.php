<?php
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
 *
 * This event is sent before $user is set to anything
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
	/**
	 * @var array
	 */
	public $args;

	/**
	 * @var int
	 */
	public $arg_count;

	/**
	 * @var int
	 */
	public $part_count;

	/**
	 * @param string $path
	 */
	public function __construct($path) {
		global $config;

		// trim starting slashes
		$path = ltrim($path, "/");

		// if path is not specified, use the default front page
		if(empty($path)) {   /* empty is faster than strlen */
			$path = $config->get_string('front_page');
		}

		// break the path into parts
		$args = explode('/', $path);

		// voodoo so that an arg can contain a slash; is
		// this still needed?
		if(strpos($path, "^") !== FALSE) {
			$unescaped = array();
			foreach($args as $part) {
				$unescaped[] = _decaret($part);
			}
			$args = $unescaped;
		}

		$this->args = $args;
		$this->arg_count = count($args);
	}

	/**
	 * Test if the requested path matches a given pattern.
	 *
	 * If it matches, store the remaining path elements in $args
	 *
	 * @param string $name
	 * @return bool
	 */
	public function page_matches(/*string*/ $name) {
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

	/**
	 * Get the n th argument of the page request (if it exists.)
	 *
	 * @param int $n
	 * @return string|null The argument (string) or NULL
	 */
	public function get_arg(/*int*/ $n) {
		$offset = $this->part_count + $n;
		if($offset >= 0 && $offset < $this->arg_count) {
			return $this->args[$offset];
		}
		else {
			return null;
		}
	}

	/**
	 * Returns the number of arguments the page request has.
	 * @return int
	 */
	public function count_args() {
		return int_escape($this->arg_count - $this->part_count);
	}

	/*
	 * Many things use these functions
	 */

	/**
	 * @return array
	 */
	public function get_search_terms() {
		$search_terms = array();
		if($this->count_args() === 2) {
			$search_terms = explode(' ', $this->get_arg(0));
		}
		return $search_terms;
	}

	/**
	 * @return int
	 */
	public function get_page_number() {
		$page_number = 1;
		if($this->count_args() === 1) {
			$page_number = int_escape($this->get_arg(0));
		}
		else if($this->count_args() === 2) {
			$page_number = int_escape($this->get_arg(1));
		}
		if($page_number === 0) $page_number = 1; // invalid -> 0
		return $page_number;
	}

	/**
	 * @return int
	 */
	public function get_page_size() {
		global $config;
		return $config->get_int('index_images');
	}
}


/**
 * Sent when index.php is called from the command line
 */
class CommandEvent extends Event {
	/**
	 * @var string
	 */
	public $cmd = "help";

	/**
	 * @var array
	 */
	public $args = array();

	/**
	 * @param string[] $args
	 */
	public function __construct(/*array(string)*/ $args) {
		global $user;

		$opts = array();
		$log_level = SCORE_LOG_WARNING;
        $arg_count = count($args);

		for($i=1; $i<$arg_count; $i++) {
			switch($args[$i]) {
				case '-u':
					$user = User::by_name($args[++$i]);
					if(is_null($user)) {
						die("Unknown user");
					}
					break;
				case '-q':
					$log_level += 10;
					break;
				case '-v':
					$log_level -= 10;
					break;
				default:
					$opts[] = $args[$i];
					break;
			}
		}

		define("CLI_LOG_LEVEL", $log_level);

		if(count($opts) > 0) {
			$this->cmd = $opts[0];
			$this->args = array_slice($opts, 1);
		}
		else {
			print "\n";
			print "Usage: php {$args[0]} [flags] [command]\n";
			print "\n";
			print "Flags:\n";
			print "  -u [username]\n";
			print "    Log in as the specified user\n";
			print "  -q / -v\n";
			print "    Be quieter / more verbose\n";
			print "    Scale is debug - info - warning - error - critical\n";
			print "    Default is to show warnings and above\n";
			print "    \n";
			print "Currently known commands:\n";
		}
	}
}


/**
 * A signal that some text needs formatting, the event carries
 * both the text and the result
 */
class TextFormattingEvent extends Event {
	/**
	 * For reference
	 *
	 * @var string
	 */
	public $original;

	/**
	 * with formatting applied
	 *
	 * @var string
	 */
	public $formatted;

	/**
	 * with formatting removed
	 *
	 * @var string
	 */
	public $stripped;

	/**
	 * @param string $text
	 */
	public function __construct(/*string*/ $text) {
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
	public $section;

	/**
	 * See python...
	 *
	 * @var int
	 */
	public $priority = 0;

	/**
	 * Free text to be logged
	 *
	 * @var string
	 */
	public $message;

	/**
	 * The time that the event was created
	 *
	 * @var int
	 */
	public $time;

	/**
	 * Extra data to be held separate
	 *
	 * @var array
	 */
	public $args;

	/**
	 * @param string $section
	 * @param int $priority
	 * @param string $message
	 * @param array $args
	 */
	public function __construct($section, $priority, $message, $args) {
		$this->section = $section;
		$this->priority = $priority;
		$this->message = $message;
		$this->args = $args;
		$this->time = time();
	}
}

