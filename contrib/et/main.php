<?php
/*
 * Name: System Info
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show various bits of system information
 * Documentation:
 *  Knowing the information that this extension shows can be
 *  very useful for debugging. There's also an option to send
 *  your stats to my database, so I can get some idea of how
 *  shimmie is used, which servers I need to support, which
 *  versions of PHP I should test with, etc.
 */

class ET implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("system_info")) {
			if($user->is_admin()) {
				$this->theme->display_info_page($page, $this->get_info());
			}
		}

		if($event instanceof UserBlockBuildingEvent) {
			if($user->is_admin()) {
				$event->add_link("System Info", make_link("system_info"));
			}
		}
	}

	private function get_info() {
		global $config, $database;
		global $_event_listeners; // yay for using secret globals \o/

		$info = array();
		$info['site_title'] = $config->get_string("title");
		$info['site_theme'] = $config->get_string("theme");
		$info['site_url']   = "http://" . $_SERVER["HTTP_HOST"] . get_base_href();

		$info['sys_shimmie'] = VERSION;
		$info['sys_schema']  = $config->get_string("db_version");
		$info['sys_php']     = phpversion();
		$info['sys_os']      = php_uname();
		$info['sys_disk']    = to_shorthand_int(disk_total_space("./") - disk_free_space("./")) . " / " .
		                       to_shorthand_int(disk_total_space("./"));
		$info['sys_server']  = $_SERVER["SERVER_SOFTWARE"];
		$proto = preg_replace("#(.*)://.*#", "$1", DATABASE_DSN);
		#$db = $database->db->ServerInfo();
		#$info['sys_db'] = "$proto / {$db['version']}";

		$info['stat_images']   = $database->get_one("SELECT COUNT(*) FROM images");
		$info['stat_comments'] = $database->get_one("SELECT COUNT(*) FROM comments");
		$info['stat_users']    = $database->get_one("SELECT COUNT(*) FROM users");
		$info['stat_tags']     = $database->get_one("SELECT COUNT(*) FROM tags");
		$info['stat_image_tags'] = $database->get_one("SELECT COUNT(*) FROM image_tags");

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
