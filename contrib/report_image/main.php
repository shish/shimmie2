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
	var $id;

	public function RemoveReportedImageEvent($id) {
		$this->id = $id;
	}
}

class AddReportedImageEvent extends Event {
	var $reporter_id;
	var $image_id;
	var $reason;

	public function AddReportedImageEvent($image_id, $reporter_id, $reason) {
		$this->reporter_id = $reporter_id;
		$this->image_id = $image_id;
		$this->reason = $reason;
	}
}

class ReportImage extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_bool('report_image_show_thumbs', true);

		if($config->get_int("ext_report_image_version") < 1) {
			$this->install();
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;
		if($event->page_matches("image_report")) {
			if($event->get_arg(0) == "add") {
				if(isset($_POST['image_id']) && isset($_POST['reason'])) {
					$image_id = int_escape($_POST['image_id']);
					send_event(new AddReportedImageEvent($image_id, $user->id, $_POST['reason']));
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/view/$image_id"));
				}
			}
			else if($event->get_arg(0) == "remove") {
				if(isset($_POST['id'])) {
					if($user->is_admin()) {
						send_event(new RemoveReportedImageEvent($_POST['id']));
						$page->set_mode("redirect");
						$page->set_redirect(make_link("image_report/list"));
					}
				}
			}
			else if($event->get_arg(0) == "list") {
				if($user->is_admin()) {
					$this->theme->display_reported_images($page, $this->get_reported_images());
				}
			}
		}
	}

	public function onAddReportedImage(AddReportedImageEvent $event) {
		global $database;
		$database->Execute(
				"INSERT INTO image_reports(image_id, reporter_id, reason)
				VALUES (?, ?, ?)",
				array($event->image_id, $event->reporter_id, $event->reason));
	}

	public function onRemoveReportedImage(RemoveReportedImageEvent $event) {
		global $database;
		$database->Execute("DELETE FROM image_reports WHERE id = ?", array($event->id));
	}

	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $config, $user, $page;
		if($user->can('report_image')) {
			$reps = $this->get_reporters($event->image);
			$this->theme->display_image_banner($event->image, $reps);
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Report Image Options");
		$sb->add_bool_option("report_image_anon", "Allow anonymous image reporting: ");
		$sb->add_bool_option("report_image_show_thumbs", "<br>Show thumbnails in admin panel: ");
		$event->panel->add_block($sb);
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("Reported Images", make_link("image_report/list"));
		}
	}

	public function onImageDeletion(ImageDeletionEvent $event) {
		global $database;
		$database->Execute("DELETE FROM image_reports WHERE image_id = ?", array($event->image->id));
	}

	protected function install() {
		global $database;
		global $config;
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

	public function get_reporters(Image $image) {
		global $database;
		return $database->get_col("
			SELECT users.name
			FROM image_reports
			JOIN users ON reporter_id = users.id
			WHERE image_reports.image_id = :image_id
		", array("image_id" => $image->id));
	}

	public function get_reported_images() {
		global $config, $database;
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
}
//  ===== Changelog =====
// * Version 0.3a / 0.3a_rc - 11/06/07 - I can no longer use the same theme.php file for both SVN and RCx. Sorry.
// *   Same deal with theme.php as it is with main.php
// * Version 0.3 / 0.3_rc - 11/06/07 - Added the option to display thumbnails, moved the reported image list to it's
//     own page, and checked to make sure the user is an admin before letting them delete / view reported images.
// * Version 0.2c_rc2 - 10/27/07 - Now (really!) supports Shimmie2 RC2!
// * Version 0.2b - 10/27/07 - Now supports Shimmie2 RC2!
// * Version 0.2a - 10/24/07 - Fixed some SQL issues. I will make sure to test before commiting :)
// * Version 0.2 - 10/24/07 - First public release.
?>
