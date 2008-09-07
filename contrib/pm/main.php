<?php
/**
 * Name: Private Messaging
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Allow users to send messages to eachother
 */

class PM implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			global $config;
			if($config->get_int("pm_version") < 1) {
				$this->install();
			}
		}

		/*
		if($event instanceof UserBlockBuildingEvent) {
			if(!$event->user->is_anonymous()) {
				$event->add_link("Private Messages", make_link("pm"));
			}
		}
		*/

		if($event instanceof UserPageBuildingEvent) {
			global $user;
			$duser = $event->user;
			if(!$user->is_anonymous()) {
				if(($user->id == $duser->id) || $user->is_admin()) {
					$this->theme->display_pms($event->page, $this->get_pms($duser));
				}
				if($user->id != $duser->id) {
					$this->theme->display_composer($event->page, $user, $duser);
				}
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("pm/read")) {
			global $database;
			global $config;
			global $user;
			$pm_id = int_escape($event->get_arg(0));
			$pm = $database->get_row("SELECT * FROM private_message WHERE id = ?", array($pm_id));
			if(is_null($pm)) {
				// error
			}
			else if(($pm["to_id"] == $user->id) || $user->is_admin()) {
				$from_user = User::by_id($config, $database, int_escape($pm["from_id"]));
				$this->theme->display_message($event->page, $from_user, $event->user, $pm);
			}
			else {
				// else
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("pm/delete")) {
			global $database;
			global $config;
			global $user;
			$pm_id = int_escape($event->get_arg(0));
			$pm = $database->get_row("SELECT * FROM private_message WHERE id = ?", array($pm_id));
			if(is_null($pm)) {
				// error
			}
			else if(($pm["to_id"] == $user->id) || $user->is_admin()) {
				$database->execute("DELETE FROM private_message WHERE id = ?", array($pm_id));
				$event->page->set_mode("redirect");
				$event->page->set_redirect(make_link("user"));
			}
			else {
				// else
			}
		}
	}

	protected function install() {
		global $database;
		global $config;
		
		// shortcut to latest
		if($config->get_int("pm_version") < 1) {
			$database->execute("
				CREATE TABLE private_message (
					id {$database->engine->auto_increment},
					from_id INTEGER NOT NULL,
					from_ip VARCHAR(15) NOT NULL,
					to_id INTEGER NOT NULL,
					sent_date DATETIME NOT NULL,
					subject VARCHAR(64) NOT NULL,
					message TEXT NOT NULL,
					is_read ENUM('Y', 'N') NOT NULL DEFAULT 'N',
					INDEX (to_id)
				) {$database->engine->create_table_extras};
			");
			$config->set_int("pm_version", 1);
		}
	}

	private function get_pms(User $user) {
		global $database;

		return $database->get_all("
			SELECT private_message.*,user_from.name AS from_name
			FROM private_message
			JOIN users AS user_from ON user_from.id=from_id
			WHERE to_id = ?
			", array($user->id));
	}
}
add_event_listener(new PM());
?>
