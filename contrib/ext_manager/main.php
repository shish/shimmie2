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
				if($event->get_arg(0) == "set") {
					if(is_writable("ext")) {
						$this->set_things($_POST);
						$event->page->set_mode("redirect");
						$event->page->set_redirect(make_link("ext_manager"));
					}
					else {
						$this->theme->display_error($event->page, "File Operation Failed",
							"The extension folder isn't writable by the web server :(");
					}
				}
				else {
					$this->theme->display_table($event->page, $this->get_extensions());
				}
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
			if(preg_match("/Name: (.*)/", $data, $matches)) {
				$extension["name"] = $matches[1];
			}
			if(preg_match("/Link: (.*)/", $data, $matches)) {
				$extension["link"] = $matches[1];
			}
			if(preg_match("/Author: (.*) [<\(](.*@.*)[>\)]/", $data, $matches)) {
				$extension["author"] = $matches[1];
				$extension["email"] = $matches[2];
			}
			else if(preg_match("/Author: (.*)/", $data, $matches)) {
				$extension["author"] = $matches[1];
			}
			if(preg_match("/Description: (.*)/", $data, $matches)) {
				$extension["description"] = $matches[1];
			}
			$extension["enabled"] = $this->is_enabled($extension["ext_name"]);
			$extensions[] = $extension;
		}
		return $extensions;
	}

	private function is_enabled($fname) {
		return file_exists("ext/$fname");
	}

	private function set_things($settings) {
		foreach(glob("contrib/*/main.php") as $main) {
			$matches = array();
			preg_match("#contrib/(.*)/main.php#", $main, $matches);
			$fname = $matches[1];

			if(!isset($settings["ext_$fname"])) $settings["ext_$fname"] = 0;
			$this->set_enabled($fname, $settings["ext_$fname"]);
		}
	}

	private function set_enabled($fname, $enabled) {
		if($enabled) {
			// enable if currently disabled
			if(!file_exists("ext/$fname")) {
				$this->enable_extension($fname);
			}
		}
		else {
			// disable if currently enabled
			if(file_exists("ext/$fname")) {
				deltree("ext/$fname");
			}
		}
	}

	private function enable_extension($fname) {
		if(function_exists("symlink")) {
			symlink("../contrib/$fname", "ext/$fname");
		}
		else {
			full_copy("contrib/$fname", "ext/$fname");
		}
	}
}
add_event_listener(new ExtManager());
?>
