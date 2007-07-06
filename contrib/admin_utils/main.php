<?php
/**
 * Name: Misc Admin Utils
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Various non-essential utilities
 */

class AdminUtils extends Extension {
	var $theme;
// event handler {{{
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("admin_utils", "AdminUtilsTheme");
		
		if(is_a($event, 'PageRequestEvent') && ($event->page == "admin_utils")) {
			global $user;
			if($user->is_admin()) {
				set_time_limit(0);
				
				switch($_POST['action']) {
					case 'lowercase all tags':
						$this->lowercase_all_tags();
						break;
					case 'recount tag use':
						$this->recount_tag_use();
						break;
					case 'purge unused tags':
						$this->purge_unused_tags();
						break;
				}

				global $page;
				$page->set_mode("redirect");
				$page->set_redirect(make_link("admin"));
			}
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			global $page;
			$this->theme->display_form($page);
		}
	}
// }}}
// do things {{{
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
// }}}
}
add_event_listener(new AdminUtils());
?>
