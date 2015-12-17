<?php
/*
 * Name: Report Images
 * Author: ATravelingGeek <atg@atravelinggeek.com>
 * Link: http://atravelinggeek.com/
 * License: GPLv2
 * Description: Report images as dupes/illegal/etc
 * Version 0.3a - See changelog in main.php
 * November 06, 2007
 */

class RemoveReportedImageEvent extends Event {
	/** @var  int */
	public $id;

	/**
	 * @param int $id
	 */
	public function __construct($id) {
		$this->id = $id;
	}
}

class AddReportedImageEvent extends Event {
	/** @var int  */
	public $reporter_id;
	/** @var int  */
	public $image_id;
	/** @var string  */
	public $reason;

	/**
	 * @param int $image_id
	 * @param int $reporter_id
	 * @param string $reason
	 */
	public function __construct($image_id, $reporter_id, $reason) {
		$this->reporter_id = $reporter_id;
		$this->image_id = $image_id;
		$this->reason = $reason;
	}
}

class ReportImage extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;

		if($config->get_int("ext_report_image_version") < 1) {
			$this->install();
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;
		if($event->page_matches("image_report")) {
			if($event->get_arg(0) == "add") {
				if(!empty($_POST['image_id']) && !empty($_POST['reason'])) {
					$image_id = int_escape($_POST['image_id']);
					send_event(new AddReportedImageEvent($image_id, $user->id, $_POST['reason']));
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/view/$image_id"));
				}
				else {
					$this->theme->display_error(500, "Missing input", "Missing image ID or report reason");
				}
			}
			else if($event->get_arg(0) == "remove") {
				if(!empty($_POST['id'])) {
					if($user->can("view_image_report")) {
						send_event(new RemoveReportedImageEvent($_POST['id']));
						$page->set_mode("redirect");
						$page->set_redirect(make_link("image_report/list"));
					}
				}
				else {
					$this->theme->display_error(500, "Missing input", "Missing image ID");
				}
			}
			else if($event->get_arg(0) == "remove_reports_by" && $user->check_auth_token()) {
				if($user->can("view_image_report")) {
					$this->delete_reports_by(int_escape($_POST['user_id']));
					$page->set_mode("redirect");
					$page->set_redirect(make_link());
				}
			}
			else if($event->get_arg(0) == "list") {
				if($user->can("view_image_report")) {
					$this->theme->display_reported_images($page, $this->get_reported_images());
				}
			}
		}
	}

	public function onAddReportedImage(AddReportedImageEvent $event) {
		global $database;
		log_info("report_image", "Adding report of Image #{$event->image_id} with reason '{$event->reason}'", false, array("image_id" => $event->image_id));
		$database->Execute(
				"INSERT INTO image_reports(image_id, reporter_id, reason)
				VALUES (?, ?, ?)",
				array($event->image_id, $event->reporter_id, $event->reason));
		$database->cache->delete("image-report-count");
	}

	public function onRemoveReportedImage(RemoveReportedImageEvent $event) {
		global $database;
		$database->Execute("DELETE FROM image_reports WHERE id = ?", array($event->id));
		$database->cache->delete("image-report-count");
	}

	public function onUserPageBuilding(UserPageBuildingEvent $event) {
		global $user;
		if($user->can("view_image_report")) {
			$this->theme->get_nuller($event->display_user);
		}
	}

	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $user;
		if($user->can('create_image_report')) {
			$reps = $this->get_reporters($event->image);
			$this->theme->display_image_banner($event->image, $reps);
		}
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->can("view_image_report")) {
			$count = $this->count_reported_images();
			$h_count = $count > 0 ? " ($count)" : "";
			$event->add_link("Reported Images$h_count", make_link("image_report/list"));
		}
	}

	public function onImageDeletion(ImageDeletionEvent $event) {
		global $database;
		$database->Execute("DELETE FROM image_reports WHERE image_id = ?", array($event->image->id));
		$database->cache->delete("image-report-count");
	}

	public function onUserDeletion(UserDeletionEvent $event) {
		$this->delete_reports_by($event->id);
	}

	/**
	 * @param int $user_id
	 */
	public function delete_reports_by($user_id) {
		global $database;
		$database->execute("DELETE FROM image_reports WHERE reporter_id=?", array($user_id));
		$database->cache->delete("image-report-count");
	}

	protected function install() {
		global $database, $config;

		if($config->get_int("ext_report_image_version") < 1) {
			$database->create_table("image_reports", "
				id SCORE_AIPK,
				image_id INTEGER NOT NULL,
				reporter_id INTEGER NOT NULL,
				reason TEXT NOT NULL,
				FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
				FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
			");
			$config->set_int("ext_report_image_version", 1);
		}
	}

	/**
	 * @param Image $image
	 * @return string[]
	 */
	public function get_reporters(Image $image) {
		global $database;

		return $database->get_col("
			SELECT users.name
			FROM image_reports
			JOIN users ON reporter_id = users.id
			WHERE image_reports.image_id = :image_id
		", array("image_id" => $image->id));
	}

	/**
	 * @return array
	 */
	public function get_reported_images() {
		global $database;

		$all_reports = $database->get_all("
			SELECT image_reports.*, users.name AS reporter_name
			FROM image_reports
			JOIN users ON reporter_id = users.id");
		if(is_null($all_reports)) $all_reports = array();

		$reports = array();
		foreach($all_reports as $report) {
			$image_id = int_escape($report['image_id']);
			$image = Image::by_id($image_id);
			if(is_null($image)) {
				send_event(new RemoveReportedImageEvent($report['id']));
				continue;
			}
			$report['image'] = $image;
			$reports[] = $report;
		}

		return $reports;
	}

	/**
	 * @return int
	 */
	public function count_reported_images() {
		global $database;

		$count = $database->cache->get("image-report-count");
		if(is_null($count) || $count === false) {
			$count = $database->get_one("SELECT count(*) FROM image_reports");
			$database->cache->set("image-report-count", $count, 600);
		}

		return $count;
	}
}
