<?php
/*
 * A generic extension class, for subclassing
 */
interface Extension {
	public function receive_event(Event $event);
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
