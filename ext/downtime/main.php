<?php

class Downtime extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("downtime", "DowntimeTheme");

		$this->check_downtime($event);

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Downtime");
			$sb->add_bool_option("downtime", "Disable non-admin access: ");
			$sb->add_longtext_option("downtime_message", "<br>");
			$event->panel->add_block($sb);
		}
		if(is_a($event, 'PageRequestEvent')) {
			global $config;
			if($config->get_bool("downtime")) {
				$this->theme->display_notification($event->page_object);
			}
		}
	}

	private function check_downtime($event) {
		global $user;
		global $config;

		if($config->get_bool("downtime") && !$user->is_admin() && 
				is_a($event, 'PageRequestEvent') && !$this->is_safe_page($event)) {
			$msg = $config->get_string("downtime_message");
			$this->theme->display_message($msg);
		}
	}

	private function is_safe_page($event) {
		if($event->page == "user" && $event->get_arg(0) == "login") return true;
		else return false;
	}
}
add_event_listener(new Downtime(), 10);
?>
