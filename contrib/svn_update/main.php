<?php
/**
 * Name: Subversion Updater
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Provides a button to check for updates
 */

class SVNUpdate extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("svn_update", "SVNUpdateTheme");
		
		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "update")) {
			if($event->user->is_admin()) {
				if($event->get_arg(0) == "log") {
					$this->theme->display_update_todo($event->page, $this->get_update_log());
				}
				if($event->get_arg(0) == "run") {
					$this->theme->display_update_log($event->page, $this->run_update());
				}
			}
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			global $page;
			$this->theme->display_form($page);
		}
	}

	private function get_update_log() {
		return shell_exec("svn log -r BASE:HEAD .");
	}
	private function run_update() {
		return shell_exec("svn update");
	}
}
add_event_listener(new SVNUpdate());
?>
