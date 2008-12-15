<?php
/**
 * Name: Bulk Add
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Bulk add server-side images
 */

class BulkAdd implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("bulk_add")) {
			if($event->user->is_admin() && isset($_POST['dir'])) {
				set_time_limit(0);

				$this->add_dir($_POST['dir']);
				$this->theme->display_upload_results($event->page);
			}
		}

		if($event instanceof AdminBuildingEvent) {
			global $page;
			$this->theme->display_admin_block($page);
		}
	}

	private function add_image($tmpname, $filename, $tags) {
		if(file_exists($tmpname)) {
			global $user;
			$pathinfo = pathinfo($filename);
			$metadata['filename'] = $pathinfo['basename'];
			$metadata['extension'] = $pathinfo['extension'];
			$metadata['tags'] = $tags;
			$metadata['source'] = null;
			$event = new DataUploadEvent($user, $tmpname, $metadata);
			send_event($event);
			if($event->vetoed) {
				return $event->veto_reason;
			}
		}
	}

	private function add_dir($base, $subdir="") {
		global $page;
		
		if(!is_dir($base)) {
			$this->theme->add_status("Error", "$base is not a directory");
			return;
		}

		$list = "";
		
		$dir = opendir("$base/$subdir");
		while($filename = readdir($dir)) {
			$fullpath = "$base/$subdir/$filename";
		
			if(is_link($fullpath)) {
				// ignore
			}
			else if(is_dir($fullpath)) {
				if($filename[0] != ".") {
					$this->add_dir($base, "$subdir/$filename");
				}
			}
			else {
				$tmpfile = $fullpath;
				$tags = $subdir;
				$tags = str_replace("/", " ", $tags);
				$tags = str_replace("__", " ", $tags);
				$list .= "<br>".html_escape("$subdir/$filename (".str_replace(" ", ",", $tags).")...");
				$error = $this->add_image($tmpfile, $filename, $tags);
				if(is_null($error)) {
					$list .= "ok\n";
				}
				else {
					$list .= "failed: $error\n";
				}
			}
		}
		closedir($dir);

		if(strlen($list) > 0) {
			$this->theme->add_status("Adding $subdir", $list);
		}
	}
}
add_event_listener(new BulkAdd());
?>
