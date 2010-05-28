<?php
/*
 * Name: Image Hash Ban
 * Author: ATravelingGeek <atg@atravelinggeek.com>
 * Link: http://atravelinggeek.com/
 * License: GPLv2
 * Description: Ban images based on their hash
 * Based on the ResolutionLimit and IPban extensions by Shish
 * Version 0.1, October 21, 2007
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
class ImageBan implements Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config, $database;
		if($config->get_int("ext_imageban_version") < 1) {
			$database->create_table("image_bans", "
				id SCORE_AIPK,
				hash CHAR(32) NOT NULL,
				date DATETIME DEFAULT SCORE_NOW,
				reason TEXT NOT NULL
			");
			$config->set_int("ext_imageban_version", 1);
		}
	}

	public function onDataUpload(DataUploadEvent $event) {
		global $database;
		$row = $database->db->GetRow("SELECT * FROM image_bans WHERE hash = ?", $event->hash);
		if($row) {
			log_info("image_hash_ban", "Blocked image ({$event->hash})");
			throw new UploadException("Image ".html_escape($row["hash"])." has been banned, reason: ".format_text($row["reason"]));
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $database, $page, $user;

		if($event->page_matches("image_hash_ban")) {
			if($user->is_admin()) {
				if($event->get_arg(0) == "add") {
					if(isset($_POST['hash']) && isset($_POST['reason'])) {
						send_event(new AddImageHashBanEvent($_POST['hash'], $_POST['reason']));

						$page->set_mode("redirect");
						$page->set_redirect(make_link("image_hash_ban/list/1"));
					}
					if(isset($_POST['image_id'])) {
						$image = Image::by_id(int_escape($_POST['image_id']));
						if($image) {
							send_event(new ImageDeletionEvent($image));
							$page->set_mode("redirect");
							$page->set_redirect(make_link("post/list"));
						}
					}
				}
				else if($event->get_arg(0) == "remove") {
					if(isset($_POST['hash'])) {
						send_event(new RemoveImageHashBanEvent($_POST['hash']));

						$page->set_mode("redirect");
						$page->set_redirect(make_link("image_hash_ban/list/1"));
					}
				}
				else if($event->get_arg(0) == "list") {
					$page_num = 0;
					if($event->count_args() == 2) {
						$page_num = int_escape($event->get_arg(1));
					}
					$page_size = 100;
					$page_count = ceil($database->db->getone("SELECT COUNT(id) FROM image_bans")/$page_size);
					$this->theme->display_Image_hash_Bans($page, $page_num, $page_count, $this->get_image_hash_bans($page_num, $page_size));
				}
			}
		}
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("Image Bans", make_link("image_hash_ban/list/1"));
		}
	}

	public function onAddImageHashBan(AddImageHashBanEvent $event) {
		$this->add_image_hash_ban($event->hash, $event->reason);
	}

	public function onRemoveImageHashBan(RemoveImageHashBanEvent $event) {
		$this->remove_image_hash_ban($event->hash);
	}

	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		global $user;
		if($user->is_admin()) {
			$event->add_part($this->theme->get_buttons_html($event->image));
		}
	}

	// DB funness

	public function get_image_hash_bans($page, $size=100) {
		// FIXME: many
		$size_i = int_escape($size);
		$offset_i = int_escape($page-1)*$size_i;
		global $database;
		$bans = $database->get_all("SELECT * FROM image_bans ORDER BY id DESC LIMIT $size_i OFFSET $offset_i");
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

	// in before resolution limit plugin
	public function get_priority() {return 30;}
}
?>
