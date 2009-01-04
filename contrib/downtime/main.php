<?php
/**
 * Name: Downtime
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show a "down for maintenance" page
 */

class Downtime implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Downtime");
			$sb->add_bool_option("downtime", "Disable non-admin access: ");
			$sb->add_longtext_option("downtime_message", "<br>");
			$event->panel->add_block($sb);
		}

		if($event instanceof PageRequestEvent) {
			if($event->context->config->get_bool("downtime")) {
				$this->check_downtime($event);
				$this->theme->display_notification($event->page);
			}
		}
	}

	private function check_downtime(PageRequestEvent $event) {
		$user = $event->context->user;
		$config = $event->context->config;

		if($config->get_bool("downtime") && !$user->is_admin() &&
				($event instanceof PageRequestEvent) && !$this->is_safe_page($event)) {
			$msg = $config->get_string("downtime_message");
			$this->theme->display_message($msg);
		}
	}

	private function is_safe_page(PageRequestEvent $event) {
		if($event->page_matches("user_admin/login")) return true;
		else return false;
	}
}
add_event_listener(new Downtime(), 10);
?>
