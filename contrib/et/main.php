<?php
/**
 * Name: System Info
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show various bits of system information, for debugging
 */

class ET implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("system_info")) {
			if($event->context->user->is_admin()) {
				$this->theme->display_info_page($event->page, $this->get_info($event->context));
			}
		}

		if($event instanceof UserBlockBuildingEvent) {
			if($event->context->user->is_admin()) {
				$event->add_link("System Info", make_link("system_info"));
			}
		}
	}

	private function get_info($context) {
		$database = $context->database;
		$config = $context->config;
		global $_event_listeners; // yay for using secret globals \o/

		$info = array();
		$info['site_title'] = $config->get_string("title");
		$info['site_theme'] = $config->get_string("theme");
		$info['site_genre'] = "[please write something here]";
		$info['site_url']   = isset($_SERVER['SCRIPT_URI']) ? dirname($_SERVER['SCRIPT_URI']) : "???";

		$info['sys_shimmie'] = VERSION;
		$info['sys_schema']  = $config->get_string("db_version");
		$info['sys_php']     = phpversion();
		$info['sys_os']      = php_uname();
		$info['sys_server']  = $_SERVER["SERVER_SOFTWARE"];
		include "config.php"; // more magical hax
		$proto = preg_replace("#(.*)://.*#", "$1", $database_dsn);
		$db = $database->db->ServerInfo();
		$info['sys_db'] = "$proto / {$db['version']}";

		$info['stat_images']   = $database->db->GetOne("SELECT COUNT(*) FROM images");
		$info['stat_comments'] = $database->db->GetOne("SELECT COUNT(*) FROM comments");
		$info['stat_users']    = $database->db->GetOne("SELECT COUNT(*) FROM users");
		$info['stat_tags']     = $database->db->GetOne("SELECT COUNT(*) FROM tags");
		$info['stat_image_tags'] = $database->db->GetOne("SELECT COUNT(*) FROM image_tags");

		$els = array();
		foreach($_event_listeners as $el) {
			$els[] = get_class($el);
		}
		$info['sys_extensions'] = join(', ', $els);

		//$cfs = array();
		//foreach($database->get_all("SELECT name, value FROM config") as $pair) {
		//	$cfs[] = $pair['name']."=".$pair['value'];
		//}
		//$info[''] = "Config: ".join(", ", $cfs);

		return $info;
	}
// }}}
}
add_event_listener(new ET());
?>
