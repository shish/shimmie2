<?php
/**
 * Name: Subversion Updater
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Provides a button to check for updates
 */

class SVNUpdate implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);
		
		if(($event instanceof PageRequestEvent) && ($event->page_name == "update")) {
			if($event->user->is_admin()) {
				if($event->get_arg(0) == "view_changes") {
					$this->theme->display_update_todo($event->page,
							$this->get_update_log(),
							$this->get_branches());
				}
				if($event->get_arg(0) == "update") {
					$this->theme->display_update_log($event->page, $this->run_update());
				}
				if($event->get_arg(0) == "dump") {
					$this->theme->display_update_log($event->page, $this->run_dump());
				}
				//if($event->get_arg(0) == "switch") {
				//	$this->theme->display_update_log($event->page, $this->run_update());
				//}
			}
		}

		if($event instanceof AdminBuildingEvent) {
			global $page;
			$this->theme->display_form($page);
		}
	}

	private function get_update_log() {
		return shell_exec("svn log -r HEAD:BASE .");
	}
	private function run_update() {
		return shell_exec("svn update");
	}
	private function run_dump() {
		global $database_dsn;
		$matches = array();

		// FIXME: MySQL specific
		if(preg_match("#^mysql://([^:]+):([^@]+)@([^/]+)/([^\?]+)#", $database_dsn, $matches)) {
			$date = date("Ymd");
			return 
				shell_exec("mysqldump -uUSER -pPASS -hHOST DATABASE | gzip > db-$date.sql.gz") .
				"\n\nDatabase dump should now be sitting in db-$date.sql.gz in the shimmie folder";
		}
		else {
			return "Couldn't parse database connection string";
		}
	}
	private function get_branches() {
		$data = shell_exec("svn ls http://svn.shishnet.org/shimmie2/branches/");
		$list = array();
		foreach(split("\n", $data) as $line) {
			$matches = array();
			if(preg_match("/branch_(\d.\d+)/", $line, $matches)) {
				$ver = $matches[1];
				$list["branch_$ver"] = "Stable ($ver.X)";
			}
		}
		ksort($list);
		$list = array_reverse($list, true);
		$list["trunk"] = "Unstable (Trunk)";
		return $list;
	}
}
add_event_listener(new SVNUpdate());
?>
