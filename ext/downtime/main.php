<?php
/*
 * Name: Downtime
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Show a "down for maintenance" page
 * Documentation:
 *  Once installed there will be some more options on the config page --
 *  Ticking "disable non-admin access" will mean that regular and anonymous
 *  users will be blocked from accessing the site, only able to view the
 *  message specified in the box.
 */

class Downtime extends Extension {
	public function get_priority() {return 10;}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Downtime");
		$sb->add_bool_option("downtime", "Disable non-admin access: ");
		$sb->add_longtext_option("downtime_message", "<br>");
		$event->panel->add_block($sb);
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $page, $user;

		if($config->get_bool("downtime")) {
			if(!$user->can("ignore_downtime") && !$this->is_safe_page($event)) {
				$msg = $config->get_string("downtime_message");
				$this->theme->display_message($msg);
				if(!defined("UNITTEST")) {  // hax D:
					header("HTTP/1.0 {$page->code} Downtime");
					print($page->data);
					exit;
				}
			}
			$this->theme->display_notification($page);
		}
	}

	private function is_safe_page(PageRequestEvent $event) {
		if($event->page_matches("user_admin/login")) return true;
		else return false;
	}
}
