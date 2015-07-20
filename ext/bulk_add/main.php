<?php
/*
 * Name: Bulk Add
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Bulk add server-side images
 * Documentation:
 *  Upload the images into a new directory via ftp or similar, go to
 *  shimmie's admin page and put that directory in the bulk add box.
 *  If there are subdirectories, they get used as tags (eg if you
 *  upload into <code>/home/bob/uploads/holiday/2008/</code> and point
 *  shimmie at <code>/home/bob/uploads</code>, then images will be
 *  tagged "holiday 2008")
 *  <p><b>Note:</b> requires the "admin" extension to be enabled
 */

class BulkAdd extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;
		if($event->page_matches("bulk_add")) {
			if($user->is_admin() && $user->check_auth_token() && isset($_POST['dir'])) {
				set_time_limit(0);
				$list = add_dir($_POST['dir']);
				if(strlen($list) > 0) {
					$this->theme->add_status("Adding files", $list);
				}
				$this->theme->display_upload_results($page);
			}
		}
	}

	public function onCommand(CommandEvent $event) {
		if($event->cmd == "help") {
			print "  bulk-add [directory]\n";
			print "	Import this directory\n\n";
		}
		if($event->cmd == "bulk-add") {
			if(count($event->args) == 1) {
				$list = add_dir($event->args[0]);
				if(strlen($list) > 0) {
					$this->theme->add_status("Adding files", $list);
				}
			}
		}
	}

	public function onAdminBuilding(AdminBuildingEvent $event) {
		$this->theme->display_admin_block();
	}
}

