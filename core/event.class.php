<?php
/*
 * Event:
 * generic parent class
 */
abstract class Event {
	var $context;

	public function __construct(RequestContext $context) {
		$this->context = $context;
	}
}


/*
 * InitExtEvent:
 * A wake-up call for extensions
 */
class InitExtEvent extends Event {}


/*
 * PageRequestEvent:
 * User requests /view/42 -> an event is generated with $args = array("view",
 * "42"); when an event handler asks $event->page_matches("view"), it returns
 * true and ignores the matched part, such that $event->count_args() = 1 and
 * $event->get_arg(0) = "42"
 */
class PageRequestEvent extends Event {
	var $args;
	var $arg_count;

	var $part_count;

	public function __construct(RequestContext $context, $args) {
		parent::__construct($context);
		$this->args = $args;
		$this->arg_count = count($args);
		$this->page = $context->page;
		$this->user = $context->user;
	}

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


/*
 * TextFormattingEvent:
 *   $original  - for reference
 *   $formatted - with formatting applied
 *   $stripped  - with formatting removed
 */
class TextFormattingEvent extends Event {
	var $original;
	var $formatted;
	var $stripped;

	public function TextFormattingEvent($text) {
		$h_text = html_escape(trim($text));
		$this->original  = $h_text;
		$this->formatted = $h_text;
		$this->stripped  = $h_text;
	}
}
?>
