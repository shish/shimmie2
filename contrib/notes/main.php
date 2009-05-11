<?php
/**
 * Name: Image Notes
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Adds notes overlaid on the images
 * Documentation:
 *  This is quite broken :(
 */

class Notes implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			if($config->get_int("ext_notes_version") < 1) {
				$this->install();
			}
		}

		if($event instanceof DisplayingImageEvent) {
			$notes = $database->get_all("SELECT * FROM image_notes WHERE image_id = ?", array($event->image->id));
			$this->theme->display_notes($page, $notes);
		}
	}

	protected function install() {
		global $database;
		global $config;
		$database->create_table("image_notes", "
			id SCORE_AIPK,
			image_id INTEGER NOT NULL,
			user_id INTEGER NOT NULL,
			owner_ip SCORE_INET NOT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			version INTEGER DEFAULT 1 NOT NULL,
			is_active SCORE_BOOL DEFAULT SCORE_BOOL_Y NOT NULL,
			x INTEGER NOT NULL,
			y INTEGER NOT NULL,
			w INTEGER NOT NULL,
			h INTEGER NOT NULL,
			body TEXT NOT NULL
		");
		$config->set_int("ext_notes_version", 1);
	}
}
add_event_listener(new Notes());
?>
