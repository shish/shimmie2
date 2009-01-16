<?php
/**
 * Name: Extension Manager
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: A thing for point & click extension management
 */

class ExtensionInfo { // {{{
	var $ext_name, $name, $link, $author, $email, $description, $documentation;

	function ExtensionInfo($main) {
		$matches = array();
		$lines = file($main);
		preg_match("#contrib/(.*)/main.php#", $main, $matches);
		$this->ext_name = $matches[1];
		$this->name = $this->ext_name;
		$this->enabled = $this->is_enabled($this->ext_name);

		for($i=0; $i<count($lines); $i++) {
			$line = $lines[$i];
			if(preg_match("/Name: (.*)/", $line, $matches)) {
				$this->name = $matches[1];
			}
			if(preg_match("/Link: (.*)/", $line, $matches)) {
				$this->link = $matches[1];
				if($this->link[0] == "/") {
					$this->link = make_link(substr($this->link, 1));
				}
			}
			if(preg_match("/Author: (.*) [<\(](.*@.*)[>\)]/", $line, $matches)) {
				$this->author = $matches[1];
				$this->email = $matches[2];
			}
			else if(preg_match("/Author: (.*)/", $line, $matches)) {
				$this->author = $matches[1];
			}
			if(preg_match("/(.*)Description: ?(.*)/", $line, $matches)) {
				$this->description = $matches[2];
				$start = $matches[1]." ";
				$start_len = strlen($start);
				while(substr($lines[$i+1], 0, $start_len) == $start) {
					$this->description .= " ".substr($lines[$i+1], $start_len);
					$i++;
				}
			}
			if(preg_match("/(.*)Documentation: ?(.*)/", $line, $matches)) {
				$this->documentation = $matches[2];
				$start = $matches[1]." ";
				$start_len = strlen($start);
				while(substr($lines[$i+1], 0, $start_len) == $start) {
					$this->documentation .= " ".substr($lines[$i+1], $start_len);
					$i++;
				}
			}
			if(preg_match("/\*\//", $line, $matches)) {
				break;
			}
		}
		if(is_null($this->link) && !is_null($this->documentation)) {
			$this->link = make_link("ext_doc/{$this->ext_name}");
		}
	}

	private function is_enabled($fname) {
		return file_exists("ext/$fname");
	}
} // }}}

class ExtManager implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("ext_manager")) {
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
			else {
				$this->theme->display_permission_denied($event->page);
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("ext_doc")) {
			$ext = $event->get_arg(0);
			$info = new ExtensionInfo("contrib/$ext/main.php");
			$this->theme->display_doc($event->page, $info);
		}

		if($event instanceof UserBlockBuildingEvent) {
			if($event->user->is_admin()) {
				$event->add_link("Extension Manager", make_link("ext_manager"));
			}
		}
	}

	private function get_extensions() {
		$extensions = array();
		foreach(glob("contrib/*/main.php") as $main) {
			$extensions[] = new ExtensionInfo($main);
		}
		return $extensions;
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
				if(function_exists("symlink")) {
					// yes, even though we are in /, and thus the path to contrib is
					// ./contrib, the link needs to be ../ because it is literal data
					// which will be interpreted relative to ./ext/ by the OS
					symlink("../contrib/$fname", "ext/$fname");
				}
				else {
					full_copy("contrib/$fname", "ext/$fname");
				}
			}
		}
		else {
			// disable if currently enabled
			if(file_exists("ext/$fname")) {
				deltree("ext/$fname");
			}
		}
	}
}
add_event_listener(new ExtManager());
?>
