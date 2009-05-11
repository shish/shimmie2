<?php
/**
 * Name: Private Messaging
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Allow users to send messages to eachother
 * Documentation:
 *  PMs show up on a user's profile page, readable by that user
 *  as well as board admins. To send a PM, visit another user's
 *  profile page and a box will be shown.
 */

class SendPMEvent extends Event {
	public function __construct($from_id, $from_ip, $to_id, $subject, $message) {
		$this->from_id = $from_id;
		$this->from_ip = $from_ip;
		$this->to_id = $to_id;
		$this->subject = $subject;
		$this->message = $message;
	}
}

class PM implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;

		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			if($config->get_int("pm_version") < 1) {
				$this->install();
			}
		}

		/*
		if($event instanceof UserBlockBuildingEvent) {
			if(!$user->is_anonymous()) {
				$event->add_link("Private Messages", make_link("pm"));
			}
		}
		*/

		if($event instanceof UserPageBuildingEvent) {
			$duser = $event->display_user;
			if(!$user->is_anonymous() && !$duser->is_anonymous()) {
				if(($user->id == $duser->id) || $user->is_admin()) {
					$this->theme->display_pms($page, $this->get_pms($duser));
				}
				if($user->id != $duser->id) {
					$this->theme->display_composer($page, $user, $duser);
				}
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("pm")) {
			if(!$user->is_anonymous()) {
				switch($event->get_arg(0)) {
					case "read":
						$pm_id = int_escape($event->get_arg(1));
						$pm = $database->get_row("SELECT * FROM private_message WHERE id = ?", array($pm_id));
						if(is_null($pm)) {
							$this->theme->display_error($page, "No such PM", "There is no PM #$pm_id");
						}
						else if(($pm["to_id"] == $user->id) || $user->is_admin()) {
							$from_user = User::by_id(int_escape($pm["from_id"]));
							$database->get_row("UPDATE private_message SET is_read='Y' WHERE id = ?", array($pm_id));
							$this->theme->display_message($page, $from_user, $user, $pm);
						}
						else {
							// permission denied
						}
						break;
					case "delete":
						$pm_id = int_escape($event->get_arg(1));
						$pm = $database->get_row("SELECT * FROM private_message WHERE id = ?", array($pm_id));
						if(is_null($pm)) {
							$this->theme->display_error($page, "No such PM", "There is no PM #$pm_id");
						}
						else if(($pm["to_id"] == $user->id) || $user->is_admin()) {
							$database->execute("DELETE FROM private_message WHERE id = ?", array($pm_id));
							log_info("pm", "Deleted PM #$pm_id");
							$page->set_mode("redirect");
							$page->set_redirect(make_link("user"));
						}
						else {
							// permission denied
						}
						break;
					case "send":
						$to_id = int_escape($_POST["to_id"]);
						$from_id = $user->id;
						$subject = $_POST["subject"];
						$message = $_POST["message"];
						send_event(new SendPMEvent($from_id, $_SERVER["REMOTE_ADDR"], $to_id, $subject, $message));
						$page->set_mode("redirect");
						$page->set_redirect(make_link($_SERVER["REFERER"]));
						break;
				}
			}
		}

		if($event instanceof SendPMEvent) {
			$database->execute("
					INSERT INTO private_message(
						from_id, from_ip, to_id,
						sent_date, subject, message)
					VALUES(?, ?, ?, now(), ?, ?)",
				array($event->from_id, $event->from_ip,
				$event->to_id, $event->subject, $event->message)
			);
			log_info("pm", "Sent PM to User #$to_id");
		}
	}

	protected function install() {
		global $config, $database;

		// shortcut to latest
		if($config->get_int("pm_version") < 1) {
			$database->create_table("private_message", "
				id SCORE_AIPK,
				from_id INTEGER NOT NULL,
				from_ip SCORE_INET NOT NULL,
				to_id INTEGER NOT NULL,
				sent_date DATETIME NOT NULL,
				subject VARCHAR(64) NOT NULL,
				message TEXT NOT NULL,
				is_read SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
				INDEX (to_id)
			");
			$config->set_int("pm_version", 1);
		}

		log_info("pm", "extension installed");
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
