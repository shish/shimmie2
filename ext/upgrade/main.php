<?php

class Upgrade implements Extension {
	public function receive_event(Event $event) {
		if($event instanceof InitExtEvent) {
			$this->do_things($event->context);
		}
	}

	private function do_things($context) {
		$config = $context->config;
		$database = $context->database;

		if(!is_numeric($config->get_string("db_version"))) {
			$config->set_int("db_version", 2);
		}

		if($config->get_int("db_version") < 6) {
			// cry :S
		}

		if($config->get_int("db_version") < 6) { // 7
			// add column image->locked
		}
	}
}
add_event_listener(new Upgrade(), 5);
?>
