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
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			global $config;
			if($config->get_int("ext_notes_version") < 1) {
				$this->install();
			}
		}

		if($event instanceof DisplayingImageEvent) {
			global $database;
			$notes = $database->get_all("SELECT * FROM image_notes WHERE image_id = ?", array($event->image->id));
			$this->theme->display_notes($event->page, $notes);
		}
	}

	protected function install() {
		global $database;
		global $config;
		$database->Execute("CREATE TABLE image_notes (
			id int(11) NOT NULL auto_increment PRIMARY KEY,
			image_id int(11) NOT NULL,
			user_id int(11) NOT NULL,
			owner_ip char(15) NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			version int(11) DEFAULT 1 NOT NULL,
			is_active enum('Y', 'N') DEFAULT 'Y' NOT NULL,
			x int(11) NOT NULL,
			y int(11) NOT NULL,
			w int(11) NOT NULL,
			h int(11) NOT NULL,
			body text NOT NULL
		)");
		$config->set_int("ext_notes_version", 1);
	}
}
add_event_listener(new Notes());
?>
