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
	var $theme;
	
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("report_image", "ReportImageTheme");
		
		if(is_a($event, 'InitExtEvent')) {
			global $config;
			
			$config->set_default_bool('report_image_show_thumbs', true);

			if($config->get_int("ext_report_image_version") < 1) {
				$this->install();
			}
		}
		
		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "image_report")) {
			global $user;
			if($event->get_arg(0) == "add") {
				if(isset($_POST['image_id']) && isset($_POST['reason'])) {
					$image_id = int_escape($_POST['image_id']);
					send_event(new AddReportedImageEvent($image_id, $event->user->id, $_POST['reason']));
					$event->page->set_mode("redirect");
					$event->page->set_redirect(make_link("post/view/$image_id"));
				}
			}
			else if($event->get_arg(0) == "remove") {
				if(isset($_POST['id'])) {
					if($event->user->is_admin()) {
						send_event(new RemoveReportedImageEvent($_POST['id']));
						$event->page->set_mode("redirect");
						$event->page->set_redirect(make_link("image_report/list"));
					}
				}
			}
			else if($event->get_arg(0) == "list") {
				if($event->user->is_admin()) {
					$this->theme->display_reported_images($event->page, $this->get_reported_images());
				}
			}
		}
		
		if(is_a($event, 'AddReportedImageEvent')) {
			global $database;
			$database->Execute(
					"INSERT INTO image_reports(image_id, reporter_id, reason)
					VALUES (?, ?, ?)",
					array($event->image_id, $event->reporter_id, $event->reason));
		}

		if(is_a($event, 'RemoveReportedImageEvent')) {
			global $database;
			$database->Execute("DELETE FROM image_reports WHERE id = ?", array($event->id));
		}
		
		if(is_a($event, 'DisplayingImageEvent')) {
			global $user;
			global $config;
			if($config->get_bool('report_image_anon') || !$user->is_anonymous()) {
				$this->theme->display_image_banner($event->page, $event->image->id);
			}
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Report Image Options");
			$sb->add_bool_option("report_image_anon", "Allow anonymous image reporting: ");
			$sb->add_bool_option("report_image_show_thumbs", "<br>Show thumbnails in admin panel: ");
			$event->panel->add_block($sb);
		}
		
		if(is_a($event, 'UserBlockBuildingEvent')) {
			if($event->user->is_admin()) {
				$event->add_link("Reported Images", make_link("image_report/list"));
			}
		}

		if(is_a($event, 'ImageDeletionEvent')) {
			global $database;
			$database->Execute("DELETE FROM image_reports WHERE image_id = ?", array($event->image->id));
		}
	}
	
	protected function install() {
		global $database;
		global $config;
		if($config->get_int("ext_report_image_version") < 1) {
			$database->Execute("CREATE TABLE image_reports (
				id {$database->engine->auto_increment},
				image_id INTEGER NOT NULL,
				reporter_id INTEGER NOT NULL,
				reason TEXT NOT NULL
			)");
			$config->set_int("ext_report_image_version", 1);
		}
	}

	public function get_reported_images() {
		global $database;
		$all_reports = $database->get_all("
			SELECT image_reports.*, users.name AS reporter_name
			FROM image_reports
			JOIN users ON reporter_id = users.id");
		if(is_null($all_reports)) $all_reports = array();
		
		$reports = array();
		foreach($all_reports as $report) {
			global $database;
			$image_id = int_escape($report['image_id']);
			$image = $database->get_image($image_id);
			if(is_null($image)) {
				send_event(new RemoveReportedImageEvent($report['id']));
				continue;
			}
			$report['image'] = $database->get_image($image_id);
			$reports[] = $report;
		}

		return $reports;
	}
}
add_event_listener(new ReportImage(), 29); // Not sure what I'm in before.

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
