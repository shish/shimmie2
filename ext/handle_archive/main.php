<?php
/*
 * Name: Handle Archives
 * Author: Shish <webmaster@shishnet.org>
 * Description: Allow users to upload archives (zip, etc)
 * Documentation:
 *  Note: requires exec() access and an external unzip command
 *  <p>Any command line unzipper should work, some examples:
 *  <p>unzip: <code>unzip -d "%d" "%f"</code>
 *  <br>7-zip: <code>7zr x -o"%d" "%f"</code>
 */

class ArchiveFileHandler extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_string('archive_extract_command', 'unzip -d "%d" "%f"');
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Archive Handler Options");
		$sb->add_text_option("archive_tmp_dir", "Temporary folder: ");
		$sb->add_text_option("archive_extract_command", "<br>Extraction command: ");
		$sb->add_label("<br>%f for archive, %d for temporary directory");
		$event->panel->add_block($sb);
	}

	public function onDataUpload(DataUploadEvent $event) {
		if($this->supported_ext($event->type)) {
			global $config;
			$tmp = sys_get_temp_dir();
			$tmpdir = "$tmp/shimmie-archive-{$event->hash}";
			$cmd = $config->get_string('archive_extract_command');
			$cmd = str_replace('%f', $event->tmpname, $cmd);
			$cmd = str_replace('%d', $tmpdir, $cmd);
			exec($cmd);
			$results = add_dir($tmpdir);
			if(count($results) > 0) {
        // FIXME no theme?
				$this->theme->add_status("Adding files", $results);
			}
			deltree($tmpdir);
			$event->image_id = -2; // default -1 = upload wasn't handled
		}
	}

	/**
	 * @param string $ext
	 * @return bool
	 */
	private function supported_ext($ext) {
		$exts = array("zip");
		return in_array(strtolower($ext), $exts);
	}
}
