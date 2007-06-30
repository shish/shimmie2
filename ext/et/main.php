<?php

class ET extends Extension {
	var $theme;
// event handler {{{
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("et", "ETTheme");

		if(is_a($event, 'PageRequestEvent') && ($event->page == "system_info")) {
			global $user;
			if($user->is_admin()) {
				$this->theme->display_info_page($event->page_object, $this->get_info());
			}
		}

		if(is_a($event, 'UserBlockBuildingEvent')) {
			if($event->user->is_admin()) {
				$event->add_link("System Info", make_link("system_info"));
			}
		}
	}
// }}}
// do it {{{
	private function get_info() {
		global $database;
		global $config;
		global $_event_listeners; // yay for using secret globals \o/

		$info = array();
		$info['site_title'] = $config->get_string("title");
		$info['site_theme'] = $config->get_string("theme");
		$info['site_genre'] = "[please write something here]";
		$info['site_url']   = isset($_SERVER['SCRIPT_URI']) ? dirname($_SERVER['SCRIPT_URI']) : "???";

		$info['sys_shimmie'] = $config->get_string("version");
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

		$els = array();
		foreach($_event_listeners as $el) {
			$els[] = get_class($el);
		}
		$info['sys_extensions'] = join(', ', $els);
		
		//$cfs = array();
		//foreach($database->db->GetAll("SELECT name, value FROM config") as $pair) {
		//	$cfs[] = $pair['name']."=".$pair['value'];
		//}
		//$info[''] = "Config: ".join(", ", $cfs);

		return $info;
	}
// }}}
}
add_event_listener(new ET());
?>
