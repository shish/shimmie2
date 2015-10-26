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

	/**
	 * @param string $hash
	 */
	public function __construct($hash) {
		$this->hash = $hash;
	}
}
// }}}
// AddImageHashBanEvent {{{
class AddImageHashBanEvent extends Event {
	var $hash;
	var $reason;

	/**
	 * @param string $hash
	 * @param string $reason
	 */
	public function __construct($hash, $reason) {
		$this->hash = $hash;
		$this->reason = $reason;
	}
}
// }}}
class ImageBan extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config, $database;
		if($config->get_int("ext_imageban_version") < 1) {
			$database->create_table("image_bans", "
				id SCORE_AIPK,
				hash CHAR(32) NOT NULL,
				date SCORE_DATETIME DEFAULT SCORE_NOW,
				reason TEXT NOT NULL
			");
			$config->set_int("ext_imageban_version", 1);
		}
	}

	public function onDataUpload(DataUploadEvent $event) {
		global $database;
		$row = $database->get_row("SELECT * FROM image_bans WHERE hash = :hash", array("hash"=>$event->hash));
		if($row) {
			log_info("image_hash_ban", "Attempted to upload a blocked image ({$event->hash} - {$row['reason']})");
			throw new UploadException("Image ".html_escape($row["hash"])." has been banned, reason: ".format_text($row["reason"]));
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $database, $page, $user;

		if($event->page_matches("image_hash_ban")) {
			if($user->can("ban_image")) {
				if($event->get_arg(0) == "add") {
					$image = isset($_POST['image_id']) ? Image::by_id(int_escape($_POST['image_id'])) : null;
					$hash = isset($_POST["hash"]) ? $_POST["hash"] : $image->hash;
					$reason = isset($_POST['reason']) ? $_POST['reason'] : "DNP";

					if($hash) {
						send_event(new AddImageHashBanEvent($hash, $reason));
						flash_message("Image ban added");

						if($image) {
							send_event(new ImageDeletionEvent($image));
							flash_message("Image deleted");
						}

						$page->set_mode("redirect");
						$page->set_redirect($_SERVER['HTTP_REFERER']);
					}
				}
				else if($event->get_arg(0) == "remove") {
					if(isset($_POST['hash'])) {
						send_event(new RemoveImageHashBanEvent($_POST['hash']));

						flash_message("Image ban removed");
						$page->set_mode("redirect");
						$page->set_redirect($_SERVER['HTTP_REFERER']);
					}
				}
				else if($event->get_arg(0) == "list") {
					$page_num = 0;
					if($event->count_args() == 2) {
						$page_num = int_escape($event->get_arg(1));
					}
					$page_size = 100;
					$page_count = ceil($database->get_one("SELECT COUNT(id) FROM image_bans")/$page_size);
					$this->theme->display_Image_hash_Bans($page, $page_num, $page_count, $this->get_image_hash_bans($page_num, $page_size));
				}
			}
		}
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->can("ban_image")) {
			$event->add_link("Image Bans", make_link("image_hash_ban/list/1"));
		}
	}

	public function onAddImageHashBan(AddImageHashBanEvent $event) {
		global $database;
		$database->Execute(
				"INSERT INTO image_bans (hash, reason, date) VALUES (?, ?, now())",
				array($event->hash, $event->reason));
		log_info("image_hash_ban", "Banned hash {$event->hash} because '{$event->reason}'");
	}

	public function onRemoveImageHashBan(RemoveImageHashBanEvent $event) {
		global $database;
		$database->Execute("DELETE FROM image_bans WHERE hash = ?", array($event->hash));
	}

	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		global $user;
		if($user->can("ban_image")) {
			$event->add_part($this->theme->get_buttons_html($event->image));
		}
	}

	// DB funness

	/**
	 * @param int $page
	 * @param int $size
	 * @return array
	 */
	public function get_image_hash_bans($page, $size=100) {
		global $database;

		// FIXME: many
		$size_i = int_escape($size);
		$offset_i = int_escape($page-1)*$size_i;
		$where = array("(1=1)");
		$args = array();
		if(!empty($_GET['hash'])) {
			$where[] = 'hash = ?';
			$args[] = $_GET['hash'];
		}
		if(!empty($_GET['reason'])) {
			$where[] = 'reason SCORE_ILIKE ?';
			$args[] = "%".$_GET['reason']."%";
		}
		$where = implode(" AND ", $where);
		$bans = $database->get_all($database->scoreql_to_sql("
			SELECT *
			FROM image_bans
			WHERE $where
			ORDER BY id DESC
			LIMIT $size_i
			OFFSET $offset_i
			"), $args);
		if($bans) {return $bans;}
		else {return array();}
	}

	// in before resolution limit plugin
	public function get_priority() {return 30;}
}

