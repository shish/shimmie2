<?php
/*
 * Name: Downtime
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show a "down for maintenance" page
 * Documentation:
 *  Once installed there will be some more options on the config page --
 *  Ticking "disable non-admin access" will mean that regular and anonymous
 *  users will be blocked from accessing the site, only able to view the
 *  message specified in the box.
 */

class Downtime implements Extension {
	var $theme;

	public function get_priority() {return 10;}

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Downtime");
			$sb->add_bool_option("downtime", "Disable non-admin access: ");
			$sb->add_longtext_option("downtime_message", "<br>");
			$event->panel->add_block($sb);
		}

		if($event instanceof PageRequestEvent) {
			if($config->get_bool("downtime")) {
				if(!$user->is_admin() && !$this->is_safe_page($event)) {
					$msg = $config->get_string("downtime_message");
					$this->theme->display_message($msg);
					exit;
				}
				$this->theme->display_notification($page);
			}
		}
	}

	private function is_safe_page(PageRequestEvent $event) {
		if($event->page_matches("user_admin/login")) return true;
		else return false;
	}
}
?>
