<?php
/**
 * Name: Misc Admin Utils
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Various non-essential utilities
 */

class AdminUtils extends Extension {
// event handler {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "admin_utils")) {
			global $user;
			if($user->is_admin()) {
				set_time_limit(0);
				
				switch($_POST['action']) {
					case 'lowercase all tags':
						$this->lowercase_all_tags();
						break;
				}

				global $page;
				$page->set_mode("redirect");
				$page->set_redirect(make_link("admin"));
			}
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			global $page;
			$page->add_main_block(new Block("Misc Admin Tools", $this->build_form()));
		}
	}
// }}}
// do things {{{
	private function lowercase_all_tags() {
		global $database;
		$database->execute("UPDATE tags SET tag=lower(tag)");
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
// admin page HTML {{{
	private function build_form() {
		$html = "
			<p><form action='".make_link("admin_utils")."' method='POST'>
				<input type='hidden' name='action' value='lowercase all tags'>
				<input type='submit' value='Lowercase All Tags'>
			</form>
		";
		return $html;
	}
// }}}
}
add_event_listener(new AdminUtils());
?>
