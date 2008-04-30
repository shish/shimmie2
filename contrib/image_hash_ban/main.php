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
class Image_Hash_Ban extends Extension {
	var $theme;
	public function receive_event($event) {
	
	if(is_null($this->theme)) $this->theme = get_theme_object("Image_Hash_Ban", "ImageBanTheme");
	
	if(is_a($event, 'InitExtEvent')) {
			global $config;
			if($config->get_int("ext_imageban_version") < 1) {
				$this->install();
			}

		}
	
		if(is_a($event, 'UploadingImageEvent')) {
		
				global $database;

			
			$image = $event->image;
			$tmp_hash = $image->hash;
			
			if ($database->db->GetOne("SELECT COUNT(*) FROM image_bans WHERE hash = ?", $tmp_hash) == 1) {
			  $event->veto("This image has been banned!");
			 }

			

		}
		
		
		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "image_hash_ban")) {
			global $user;
			if($user->is_admin()) {
				if($event->get_arg(0) == "add") {
					if(isset($_POST['hash']) && isset($_POST['reason'])) {
						send_event(new AddImageHashBanEvent($_POST['hash'], $_POST['reason']));

						global $page;
						$page->set_mode("redirect");
						$page->set_redirect(make_link("admin"));

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
		
		if(is_a($event, 'AdminBuildingEvent')) {
			global $page;
			$this->theme->display_Image_hash_Bans($page, $this->get_image_hash_bans());
		}
		
				if(is_a($event, 'AddImageHashBanEvent')) {
			$this->add_image_hash_ban($event->hash, $event->reason);
		}

		if(is_a($event, 'RemoveImageHashBanEvent')) {
			$this->remove_image_hash_ban($event->hash);
		}
		
				if(is_a($event, 'DisplayingImageEvent')) {
			global $user;
			if($user->is_admin()) {
			
				$this->theme->display_image_banner($event->page, $event->image->hash);
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
		$bans = $database->db->GetAll("SELECT * FROM image_bans");
		if($bans) {return $bans;}
		else {return array();}
		}
		
		public function get_image_hash_ban($hash) {
		global $database;
		// yes, this is "? LIKE var", because ? is the thing with matching tokens
		// actually, slow
		// return $database->db->GetRow("SELECT * FROM bans WHERE ? LIKE ip AND date < now() AND (end > now() OR isnull(end))", array($ip));
		return $database->db->GetRow("SELECT * FROM image_bans WHERE hash = ? AND date < now()", array($hash));
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
