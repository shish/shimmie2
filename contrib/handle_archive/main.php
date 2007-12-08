<?php
/**
 * Name: Archive File Handler
 * Author: Shish <webmaster@shishnet.org>
 * Description: Allow users to upload archives (zip, etc)
 */

class ArchiveFileHandler extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'DataUploadEvent') && $this->supported_ext($event->type)) {
			$tmpdir = "/tmp/shimmie-archive-{$event->hash}";
			system("unzip -d $tmpdir {$event->tmpname}");
			$this->add_dir($tmpdir);
			unlink($tmpdir);
		}
	}

	private function supported_ext($ext) {
		$exts = array("zip");
		$ext = strtolower($ext);
		foreach($exts as $supported) {
			if($ext == $supported) return true;
		}
		return false;
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
			$event = new DataUploadEvent($user, $tmpname, $metadata);
			send_event($event);
			if($event->vetoed) {
				return $event->veto_reason;
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
		
			if(is_dir($fullpath)) {
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
