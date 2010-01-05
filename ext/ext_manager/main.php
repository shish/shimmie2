<?php
/**
 * Name: Extension Manager
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Visibility: admin
 * Description: A thing for point & click extension management
 * Documentation:
 *   Allows the admin to view a list of all extensions and enable or
 *   disable them; also allows users to view the list of activated
 *   extensions and read their documentation
 */

/** @private */
function __extman_extcmp(ExtensionInfo $a, ExtensionInfo $b) {
	return strcmp($a->name, $b->name);
}

/** @private */
class ExtensionInfo {
	var $ext_name, $name, $link, $author, $email;
	var $description, $documentation, $version, $visibility;

	function ExtensionInfo($main) {
		$matches = array();
		$lines = file($main);
		preg_match("#(ext|contrib)/(.*)/main.php#", $main, $matches);
		$this->ext_name = $matches[2];
		$this->name = $this->ext_name;
		$this->enabled = $this->is_enabled($this->ext_name);

		for($i=0; $i<count($lines); $i++) {
			$line = $lines[$i];
			if(preg_match("/Name: (.*)/", $line, $matches)) {
				$this->name = $matches[1];
			}
			if(preg_match("/Visibility: (.*)/", $line, $matches)) {
				$this->visibility = $matches[1];
			}
			if(preg_match("/Link: (.*)/", $line, $matches)) {
				$this->link = $matches[1];
				if($this->link[0] == "/") {
					$this->link = make_link(substr($this->link, 1));
				}
			}
			if(preg_match("/Version: (.*)/", $line, $matches)) {
				$this->version = $matches[1];
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
				$this->documentation = str_replace('$site', make_http(get_base_href()), $this->documentation);
			}
			if(preg_match("/\*\//", $line, $matches)) {
				break;
			}
		}
	}

	private function is_enabled($fname) {
		if(file_exists("ext/$fname") && file_exists("contrib/$fname")) return true; // both
		if(file_exists("contrib/$fname")) return false; // only disabled (optional)
		return null; // only active (core)
	}
}

class ExtManager extends SimpleExtension {
	public function onPageRequest($event) {
		global $page, $user;
		if($event->page_matches("ext_manager")) {
			if($user->is_admin()) {
				if($event->get_arg(0) == "set") {
					if(is_writable("ext")) {
						$this->set_things($_POST);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("ext_manager"));
					}
					else {
						$this->theme->display_error($page, "File Operation Failed",
							"The extension folder isn't writable by the web server :(");
					}
				}
				else {
					$this->theme->display_table($page, $this->get_extensions(true), true);
				}
			}
			else {
				$this->theme->display_table($page, $this->get_extensions(false), false);
			}
		}

		if($event->page_matches("ext_doc")) {
			$ext = $event->get_arg(0);
			if(file_exists("ext/$ext/main.php")) {
				$info = new ExtensionInfo("ext/$ext/main.php");
			}
			else {
				$info = new ExtensionInfo("contrib/$ext/main.php");
			}
			$this->theme->display_doc($page, $info);
		}
	}

	public function onUserBlockBuilding($event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("Extension Manager", make_link("ext_manager"));
		}
		else {
			$event->add_link("Help", make_link("ext_manager"));
		}
	}


	private function get_extensions($all) {
		$extensions = array();
		if($all) {
			$exts = glob("ext/*/main.php");
			foreach(glob("contrib/*/main.php") as $ae) {
				if(!in_array("ext".substr($ae, 7), $exts)) {
					$exts[] = $ae;
				}
			}
		}
		else {
			$exts = glob("ext/*/main.php");
		}
		foreach($exts as $main) {
			$extensions[] = new ExtensionInfo($main);
		}
		usort($extensions, "__extman_extcmp");
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
				log_info("ext_manager", "Enabling $fname");
			}
		}
		else {
			// disable if currently enabled
			if(file_exists("ext/$fname")) {
				deltree("ext/$fname");
				log_info("ext_manager", "Disabling $fname");
			}
		}
	}
}
?>
