<?php
/**
 * Name: Bookmarks
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Allow users to bookmark searches
 */

class Bookmarks implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("bookmark")) {
			$user = $event->context->user;

			if($event->get_arg(0) == "add") {
				if(isset($_POST['url'])) {
					$event->page->set_mode("redirect");
					$event->page->set_redirect(make_link("user"));
				}
			}
			else if($event->get_arg(0) == "remove") {
				if(isset($_POST['id'])) {
					$event->page->set_mode("redirect");
					$event->page->set_redirect(make_link("user"));
				}
			}
		}
	}

	protected function install() {
		global $database;
		global $config;

		// shortcut to latest
		if($config->get_int("ext_bookmarks_version") < 1) {
			$database->create_table("bookmark", "
				id SCORE_AIPK,
				owner_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
				url TEXT NOT NULL,
				title TET NOT NULL,
				INDEX (owner_id)
			");
			$config->set_int("ext_bookmarks_version", 1);
		}
	}

	private function get_bookmarks() {
		global $database;
		$bms = $database->get_all("
			SELECT *
			FROM bookmark
			WHERE bookmark.owner_id = ?
		");
		if($bms) {return $bms;}
		else {return array();}
	}

	private function add_bookmark($url, $title) {
		global $database;
		$sql = "INSERT INTO bookmark(owner_id, url, title) VALUES (?, ?, ?)";
		$database->Execute($sql, array($user->id, $url, $title));
	}
}
add_event_listener(new Bookmarks());
?>
