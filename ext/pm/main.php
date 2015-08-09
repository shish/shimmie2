<?php
/*
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
	var $pm;

	public function __construct(PM $pm) {
		$this->pm = $pm;
	}
}

class PM {
	var $id, $from_id, $from_ip, $to_id, $sent_date, $subject, $message, $is_read;

	public function __construct($from_id=0, $from_ip="0.0.0.0", $to_id=0, $subject="A Message", $message="Some Text", $read=False) {
		# PHP: the P stands for "really", the H stands for "awful" and the other P stands for "language"
		if(is_array($from_id)) {
			$a = $from_id;
			$this->id      = $a["id"];
			$this->from_id = $a["from_id"];
			$this->from_ip = $a["from_ip"];
			$this->to_id   = $a["to_id"];
			$this->sent_date = $a["sent_date"];
			$this->subject = $a["subject"];
			$this->message = $a["message"];
			$this->is_read = bool_escape($a["is_read"]);
		}
		else {
			$this->id      = -1;
			$this->from_id = $from_id;
			$this->from_ip = $from_ip;
			$this->to_id   = $to_id;
			$this->subject = $subject;
			$this->message = $message;
			$this->is_read = $read;
		}
	}
}

class PrivMsg extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config, $database;

		// shortcut to latest
		if($config->get_int("pm_version") < 1) {
			$database->create_table("private_message", "
				id SCORE_AIPK,
				from_id INTEGER NOT NULL,
				from_ip SCORE_INET NOT NULL,
				to_id INTEGER NOT NULL,
				sent_date SCORE_DATETIME NOT NULL,
				subject VARCHAR(64) NOT NULL,
				message TEXT NOT NULL,
				is_read SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
				FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
				FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE
			");
			$database->execute("CREATE INDEX private_message__to_id ON private_message(to_id)");
			$config->set_int("pm_version", 2);
			log_info("pm", "extension installed");
		}

		if($config->get_int("pm_version") < 2) {
			log_info("pm", "Adding foreign keys to private messages");
			$database->Execute("delete from private_message where to_id not in (select id from users);");
			$database->Execute("delete from private_message where from_id not in (select id from users);");
			$database->Execute("ALTER TABLE private_message 
			ADD FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
			ADD FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE;");
			$config->set_int("pm_version", 2);
			log_info("pm", "extension installed");
		}
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if(!$user->is_anonymous()) {
			$count = $this->count_pms($user);
			$h_count = $count > 0 ? " <span class='unread'>($count)</span>" : "";
			$event->add_link("Private Messages$h_count", make_link("user#private-messages"));
		}
	}

	public function onUserPageBuilding(UserPageBuildingEvent $event) {
		global $page, $user;
		$duser = $event->display_user;
		if(!$user->is_anonymous() && !$duser->is_anonymous()) {
			if(($user->id == $duser->id) || $user->can("view_other_pms")) {
				$this->theme->display_pms($page, $this->get_pms($duser));
			}
			if($user->id != $duser->id) {
				$this->theme->display_composer($page, $user, $duser);
			}
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $database, $page, $user;
		if($event->page_matches("pm")) {
			if(!$user->is_anonymous()) {
				switch($event->get_arg(0)) {
					case "read":
						$pm_id = int_escape($event->get_arg(1));
						$pm = $database->get_row("SELECT * FROM private_message WHERE id = :id", array("id" => $pm_id));
						if(is_null($pm)) {
							$this->theme->display_error(404, "No such PM", "There is no PM #$pm_id");
						}
						else if(($pm["to_id"] == $user->id) || $user->can("view_other_pms")) {
							$from_user = User::by_id(int_escape($pm["from_id"]));
							if($pm["to_id"] == $user->id) {
								$database->execute("UPDATE private_message SET is_read='Y' WHERE id = :id", array("id" => $pm_id));
								$database->cache->delete("pm-count-{$user->id}");
							}
							$this->theme->display_message($page, $from_user, $user, new PM($pm));
						}
						else {
							// permission denied
						}
						break;
					case "delete":
						if($user->check_auth_token()) {
							$pm_id = int_escape($_POST["pm_id"]);
							$pm = $database->get_row("SELECT * FROM private_message WHERE id = :id", array("id" => $pm_id));
							if(is_null($pm)) {
								$this->theme->display_error(404, "No such PM", "There is no PM #$pm_id");
							}
							else if(($pm["to_id"] == $user->id) || $user->can("view_other_pms")) {
								$database->execute("DELETE FROM private_message WHERE id = :id", array("id" => $pm_id));
								$database->cache->delete("pm-count-{$user->id}");
								log_info("pm", "Deleted PM #$pm_id", "PM deleted");
								$page->set_mode("redirect");
								$page->set_redirect($_SERVER["HTTP_REFERER"]);
							}
						}
						break;
					case "send":
						if($user->check_auth_token()) {
							$to_id = int_escape($_POST["to_id"]);
							$from_id = $user->id;
							$subject = $_POST["subject"];
							$message = $_POST["message"];
							send_event(new SendPMEvent(new PM($from_id, $_SERVER["REMOTE_ADDR"], $to_id, $subject, $message)));
							flash_message("PM sent");
							$page->set_mode("redirect");
							$page->set_redirect($_SERVER["HTTP_REFERER"]);
						}
						break;
					default:
						$this->theme->display_error(400, "Invalid action", "That's not something you can do with a PM");
						break;
				}
			}
		}
	}

	public function onSendPM(SendPMEvent $event) {
		global $database;
		$database->execute("
				INSERT INTO private_message(
					from_id, from_ip, to_id,
					sent_date, subject, message)
				VALUES(:fromid, :fromip, :toid, now(), :subject, :message)",
			array("fromid" => $event->pm->from_id, "fromip" => $event->pm->from_ip,
			"toid" => $event->pm->to_id, "subject" => $event->pm->subject, "message" => $event->pm->message)
		);
		$database->cache->delete("pm-count-{$event->pm->to_id}");
		log_info("pm", "Sent PM to User #{$event->pm->to_id}");
	}


	private function get_pms(User $user) {
		global $database;

		$arr = $database->get_all("
				SELECT private_message.*,user_from.name AS from_name
				FROM private_message
				JOIN users AS user_from ON user_from.id=from_id
				WHERE to_id = :toid
				ORDER BY sent_date DESC",
			array("toid" => $user->id));
		$pms = array();
		foreach($arr as $pm) {
			$pms[] = new PM($pm);
		}
		return $pms;
	}

	private function count_pms(User $user) {
		global $database;

		$count = $database->cache->get("pm-count:{$user->id}");
		if(is_null($count) || $count === false) {
			$count = $database->get_one("
					SELECT count(*)
					FROM private_message
					WHERE to_id = :to_id
					AND is_read = :is_read
			", array("to_id" => $user->id, "is_read" => "N"));
			$database->cache->set("pm-count:{$user->id}", $count, 600);
		}
		return $count;
	}
}

