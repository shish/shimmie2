<?php
/*
 * A generic extension class, for subclassing
 */
interface Extension {
	public function receive_event(Event $event);
}

/*
 * BlahEvent -> onBlah
 */
abstract class SimpleExtension implements Extension {
	var $theme;
	var $_child;

	public function i_am($child) {
		$this->_child = $child;
		if(is_null($this->theme)) $this->theme = get_theme_object($child, false);
	}

	public function receive_event(Event $event) {
		$name = get_class($event);
		$name = "on".str_replace("Event", "", $name);
		if(method_exists($this->_child, $name)) {
			$this->_child->$name($event);
		}
	}
}

/*
 * Several extensions have this in common, make a common API
 */
abstract class FormatterExtension implements Extension {
	public function receive_event(Event $event) {
		if($event instanceof TextFormattingEvent) {
			$event->formatted = $this->format($event->formatted);
			$event->stripped  = $this->strip($event->stripped);
		}
	}

	abstract public function format($text);
	abstract public function strip($text);
}
?>
