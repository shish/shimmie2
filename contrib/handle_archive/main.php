<?php
/**
 * Name: Archive File Handler
 * Author: Shish <webmaster@shishnet.org>
 * Description: Allow users to upload archives (zip, etc)
 * Documentation:
 *  Note: requires exec() access and an external unzip command
 *  <p>Any command line unzipper should work, some examples:
 *  <p>unzip: <code>unzip -d "%d" "%f"</code>
 *  <br>7-zip: <code>7zr x -o"%d" "%f"</code>
 */

class ArchiveFileHandler implements Extension {
	public function receive_event(Event $event) {
		if($event instanceof InitExtEvent) {
			global $config;
			$config->set_default_string('archive_extract_command', 'unzip -d "%d" "%f"');
		}

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Archive Handler Options");
			$sb->add_text_option("archive_tmp_dir", "Temporary folder: ");
			$sb->add_text_option("archive_extract_command", "<br>Extraction command: ");
			$sb->add_label("<br>%f for archive, %d for temporary directory");
			$event->panel->add_block($sb);
		}

		if(($event instanceof DataUploadEvent) && $this->supported_ext($event->type)) {
			global $config;
			$tmp = sys_get_temp_dir();
			$tmpdir = "$tmp/shimmie-archive-{$event->hash}";
			$cmd = $config->get_string('archive_extract_command');
			$cmd = str_replace('%f', $event->tmpfile);
			$cmd = str_replace('%d', $tmpdir);
			system($cmd);
			$this->add_dir($tmpdir);
			unlink($tmpdir);
		}
	}

	private function supported_ext($ext) {
		$exts = array("zip");
		return array_contains($exts, strtolower($ext));
	}

	// copied from bulk add extension
	private function add_image($tmpname, $filename, $tags) {
		if(file_exists($tmpname)) {
			global $user;
			$pathinfo = pathinfo($filename);
			$metadata['filename'] = $pathinfo['basename'];
			$metadata['extension'] = $pathinfo['extension'];
			$metadata['tags'] = $tags;
			$metadata['source'] = null;
			try {
				$event = new DataUploadEvent($user, $tmpname, $metadata);
				send_event($event);
			}
			catch(UploadException $ex) {
				return $ex->getMessage();
			}
		}
	}

	// copied from bulk add extension
	private function add_dir($base, $subdir="") {
		global $page;

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

		// $this->theme->add_status("Adding $subdir", $list);
	}
}
add_event_listener(new ArchiveFileHandler());
?>
