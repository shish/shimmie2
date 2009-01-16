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
	public function __construct(RequestContext $reqest, $from_id, $from_ip, $to_id, $subject, $message) {
		parent::__construct($request);
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
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			if($event->context->config->get_int("pm_version") < 1) {
				$this->install($event->context);
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
			$user = $event->context->user;
			$duser = $event->display_user;
			if(!$user->is_anonymous() && !$duser->is_anonymous()) {
				if(($user->id == $duser->id) || $user->is_admin()) {
					$this->theme->display_pms($event->context->page, $this->get_pms($duser));
				}
				if($user->id != $duser->id) {
					$this->theme->display_composer($event->context->page, $user, $duser);
				}
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("pm")) {
			$database = $event->context->database;
			$config = $event->context->config;
			$user = $event->config->user;
			if(!$user->is_anonymous()) {
				switch($event->get_arg(0)) {
					case "read":
						$pm_id = int_escape($event->get_arg(1));
						$pm = $database->get_row("SELECT * FROM private_message WHERE id = ?", array($pm_id));
						if(is_null($pm)) {
							$this->theme->display_error($event->page, "No such PM", "There is no PM #$pm_id");
						}
						else if(($pm["to_id"] == $user->id) || $user->is_admin()) {
							$from_user = User::by_id($config, $database, int_escape($pm["from_id"]));
							$database->get_row("UPDATE private_message SET is_read='Y' WHERE id = ?", array($pm_id));
							$this->theme->display_message($event->page, $from_user, $event->user, $pm);
						}
						else {
							// permission denied
						}
						break;
					case "delete":
						$pm_id = int_escape($event->get_arg(1));
						$pm = $database->get_row("SELECT * FROM private_message WHERE id = ?", array($pm_id));
						if(is_null($pm)) {
							$this->theme->display_error($event->page, "No such PM", "There is no PM #$pm_id");
						}
						else if(($pm["to_id"] == $user->id) || $user->is_admin()) {
							$database->execute("DELETE FROM private_message WHERE id = ?", array($pm_id));
							$event->page->set_mode("redirect");
							$event->page->set_redirect(make_link("user"));
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
						send_event(new SendPMEvent($event->context, $from_id, $_SERVER["REMOTE_ADDR"], $to_id, $subject, $message));
						$event->page->set_mode("redirect");
						$event->page->set_redirect(make_link($_SERVER["REFERER"]));
						break;
				}
			}
		}

		if($event instanceof SendPMEvent) {
			$event->context->database->execute("
					INSERT INTO private_message(
						from_id, from_ip, to_id,
						sent_date, subject, message)
					VALUES(?, ?, ?, now(), ?, ?)",
				array($event->from_id, $event->from_ip,
				$event->to_id, $event->subject, $event->message)
			);
		}
	}

	protected function install(RequestContext $context) {
		$database = $context->database;
		$config = $context->config;

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
