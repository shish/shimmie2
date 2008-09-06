<?php
/**
 * Name: Image Hash Ban
 * Author: ATravelingGeek <atg@atravelinggeek.com>
 * Link: http://atravelinggeek.com/
 * License: GPLv2
 * Description: Ban images based on their hash
 * Based on the ResolutionLimit and IPban extensions by Shish
 * Version 0.1
 * October 21, 2007
 */
 
 // RemoveImageHashBanEvent {{{
class RemoveImageHashBanEvent extends Event {
	var $hash;

	public function RemoveImageHashBanEvent($hash) {
		$this->hash = $hash;
	}
}
// }}}
// AddImageHashBanEvent {{{
class AddImageHashBanEvent extends Event { 
	var $hash;
	var $reason;

	public function AddImageHashBanEvent($hash, $reason) {
		$this->hash = $hash;
		$this->reason = $reason;
	}
}
// }}}
class Image_Hash_Ban implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			global $config;
			if($config->get_int("ext_imageban_version") < 1) {
				$this->install();
			}
		}

		if($event instanceof DataUploadEvent) {
			global $database;

			$row = $database->db->GetRow("SELECT * FROM image_bans WHERE hash = ?", $event->hash);
			if($row) {
				$event->veto("Image ".html_escape($row["hash"])." has been banned, reason: ".format_text($row["reason"]));
			}
		}

		if(($event instanceof PageRequestEvent) && ($event->page_name == "image_hash_ban")) {
			if($event->user->is_admin()) {
				if($event->get_arg(0) == "add") {
					if(isset($_POST['hash']) && isset($_POST['reason'])) {
						send_event(new AddImageHashBanEvent($_POST['hash'], $_POST['reason']));

						global $page;
						$page->set_mode("redirect");
						$page->set_redirect(make_link("admin"));
					}
					if(isset($_POST['image_id'])) {
						global $database;
						$image = $database->get_image($_POST['image_id']);
						if($image) {
							send_event(new ImageDeletionEvent($image));
							$event->page->set_mode("redirect");
							$event->page->set_redirect(make_link("post/list"));
						}
					}
				}
				else if($event->get_arg(0) == "remove") {
					if(isset($_POST['hash'])) {
						send_event(new RemoveImageHashBanEvent($_POST['hash']));

						global $page;
						$page->set_mode("redirect");
						$page->set_redirect(make_link("admin"));
					}
				}
			}
		}

		if($event instanceof AdminBuildingEvent) {
			global $page;
			$this->theme->display_Image_hash_Bans($page, $this->get_image_hash_bans());
		}

		if($event instanceof AddImageHashBanEvent) {
			$this->add_image_hash_ban($event->hash, $event->reason);
		}

		if($event instanceof RemoveImageHashBanEvent) {
			$this->remove_image_hash_ban($event->hash);
		}

		if($event instanceof ImageAdminBlockBuildingEvent) {
			if($event->user->is_admin()) {
				$event->add_part($this->theme->get_buttons_html($event->image));
			}
		}
	}

	protected function install() {
		global $database;
		global $config;
		$database->Execute("CREATE TABLE image_bans (
			id int(11) NOT NULL auto_increment,
			   hash char(32) default NULL,
			   date datetime default NULL,
			   reason varchar(255) default NULL,
			   PRIMARY KEY (id)
				   )");
		$config->set_int("ext_imageban_version", 1);
	}

	// DB funness

	public function get_image_hash_bans() {
		// FIXME: many
		global $database;
		$bans = $database->get_all("SELECT * FROM image_bans");
		if($bans) {return $bans;}
		else {return array();}
	}

	public function add_image_hash_ban($hash, $reason) {
		global $database;
		$database->Execute(
				"INSERT INTO image_bans (hash, reason, date) VALUES (?, ?, now())",
				array($hash, $reason));
	}

	public function remove_image_hash_ban($hash) {
		global $database;
		$database->Execute("DELETE FROM image_bans WHERE hash = ?", array($hash));
	}

}
add_event_listener(new Image_Hash_Ban(), 30); // in before resolution limit plugin
?>
