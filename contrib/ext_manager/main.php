<?php
/**
 * Name: Extension Manager
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: A thing for point & click extension management
 */

class ExtManager extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("ext_manager", "ExtManagerTheme");
		
		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "ext_manager")) {
			if($event->user->is_admin()) {
				$this->theme->display_table($event->page, $this->get_extensions());
			}
		}

		if(is_a($event, 'UserBlockBuildingEvent')) {
			if($event->user->is_admin()) {
				$event->add_link("Extension Manager", make_link("ext_manager"));
			}
		}
	}

	private function get_extensions() {
		$extensions = array();
		foreach(glob("contrib/*/main.php") as $main) {
			$extension = array();
			$matches = array();
			$data = file_get_contents($main);
			preg_match("#contrib/(.*)/main.php#", $main, $matches);
			$extension["ext_name"] = $matches[1];
			preg_match("/Name: (.*)/", $data, $matches);
			$extension["name"] = $matches[1];
			preg_match("/Link: (.*)/", $data, $matches);
			$extension["link"] = $matches[1];
			preg_match("/Author: (.*) <(.*)>/", $data, $matches);
			$extension["author"] = $matches[1];
			$extension["email"] = $matches[2];
			preg_match("/Description: (.*)/", $data, $matches);
			$extension["description"] = $matches[1];
			$extension["enabled"] = $this->is_enabled($extension["ext_name"]);
			$extensions[] = $extension;
		}
		return $extensions;
	}

	private function is_enabled($fname) {
		return file_exists("ext/$fname");
	}
}
add_event_listener(new ExtManager());
?>
