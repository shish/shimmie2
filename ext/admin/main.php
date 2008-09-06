<?php
/* AdminBuildingEvent {{{
 *
 * Sent when the admin page is ready to be added to
 */
class AdminBuildingEvent extends Event {
	var $page;
	public function AdminBuildingEvent($page) {
		$this->page = $page;
	}
}
// }}}

class AdminPage implements Extension {
	var $theme;
	
	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("admin")) {
			if(!$event->user->is_admin()) {
				$this->theme->display_error($event->page, "Permission Denied", "This page is for admins only");
			}
			else {
				if($event->get_arg(0) == "delete_image") {
					// FIXME: missing lots of else {complain}
					if(isset($_POST['image_id'])) {
						global $config;
						global $database;
						$image = Image::by_id($config, $database, $_POST['image_id']);
						if($image) {
							send_event(new ImageDeletionEvent($image));
							$event->page->set_mode("redirect");
							$event->page->set_redirect(make_link("post/list"));
						}
					}
				}
				else {
					send_event(new AdminBuildingEvent($event->page));
				}
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("admin_utils")) {
			if($event->user->is_admin()) {
				set_time_limit(0);
				$redirect = false;
				
				switch($_POST['action']) {
					case 'lowercase all tags':
						$this->lowercase_all_tags();
						$redirect = true;
						break;
					case 'recount tag use':
						$this->recount_tag_use();
						$redirect = true;
						break;
					case 'purge unused tags':
						$this->purge_unused_tags();
						$redirect = true;
						break;
					case 'database dump':
						$this->dbdump($event->page);
						break;
				}

				if($redirect) {
					$event->page->set_mode("redirect");
					$event->page->set_redirect(make_link("admin"));
				}
			}
		}

		if($event instanceof ImageAdminBlockBuildingEvent) {
			if($event->user->is_admin()) {
				$event->add_part($this->theme->get_deleter_html($event->image->id));
			}
		}

		if($event instanceof AdminBuildingEvent) {
			$this->theme->display_page($event->page);
			$this->theme->display_form($event->page);
		}

		if($event instanceof UserBlockBuildingEvent) {
			if($event->user->is_admin()) {
				$event->add_link("Board Admin", make_link("admin"));
			}
		}
	}

	private function lowercase_all_tags() {
		global $database;
		$database->execute("UPDATE tags SET tag=lower(tag)");
	}

	private function recount_tag_use() {
		global $database;
		$database->Execute("UPDATE tags SET count=(SELECT COUNT(image_id) FROM image_tags WHERE tag_id=tags.id GROUP BY tag_id)");
	}

	private function purge_unused_tags() {
		global $database;
		$this->recount_tag_use();
		$database->Execute("DELETE FROM tags WHERE count=0");
	}

	private function dbdump($page) {
		include "config.php";

		$matches = array();
		preg_match("#(\w+)://(\w+):(\w+)@([\w\.\-]+)/([\w_]+)(\?.*)?#", $database_dsn, $matches);
		$software = $matches[1];
		$username = $matches[2];
		$password = $matches[3];
		$hostname = $matches[4];
		$database = $matches[5];

		switch($software) {
			case 'mysql':
				$cmd = "mysqldump -h$hostname -u$username -p$password $database";
				break;
		}
		
		$page->set_mode("data");
		$page->set_type("application/x-unknown");
		$page->set_filename('shimmie-'.date('Ymd').'.sql');
		$page->set_data(shell_exec($cmd));
	}

	private function check_for_orphanned_images() {
		$orphans = array();
		foreach(glob("images/*") as $dir) {
			foreach(glob("$dir/*") as $file) {
				$hash = str_replace("$dir/", "", $file);
				if(!$this->db_has_hash($hash)) {
					$orphans[] = $hash;
				}
			}
		}
	}
}
add_event_listener(new AdminPage());
?>
