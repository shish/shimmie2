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
	public function __construct($pm) {
		$this->pm = $pm;
	}
}

class PM {
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
			$this->is_read = undb_bool($a["is_read"]);
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

class PrivMsg extends SimpleExtension {
	public function onInitExt($event) {
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
			log_info("pm", "extension installed");
		}
	}

	/*
	public function onUserBlockBuilding($event) {
		global $user;
		if(!$user->is_anonymous()) {
			$event->add_link("Private Messages", make_link("pm"));
		}
	}
	*/

	public function onUserPageBuilding($event) {
		global $page, $user;
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

	public function onPageRequest($event) {
		global $database, $page, $user;
		if($event->page_matches("pm")) {
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
							$this->theme->display_message($page, $from_user, $user, new PM($pm));
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
							$page->set_redirect($_SERVER["HTTP_REFERER"]);
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
						send_event(new SendPMEvent(new PM($from_id, $_SERVER["REMOTE_ADDR"], $to_id, $subject, $message)));
						$page->set_mode("redirect");
						$page->set_redirect($_SERVER["HTTP_REFERER"]);
						break;
					default:
						$this->theme->display_error($page, "Invalid action", "That's not something you can do with a PM");
						break;
				}
			}
		}
	}

	public function onSendPM($event) {
		global $database;
		$database->execute("
				INSERT INTO private_message(
					from_id, from_ip, to_id,
					sent_date, subject, message)
				VALUES(?, ?, ?, now(), ?, ?)",
			array($event->pm->from_id, $event->pm->from_ip,
			$event->pm->to_id, $event->pm->subject, $event->pm->message)
		);
		log_info("pm", "Sent PM to User #{$event->pm->to_id}");
	}


	private function get_pms(User $user) {
		global $database;

		$arr = $database->get_all("
			SELECT private_message.*,user_from.name AS from_name
			FROM private_message
			JOIN users AS user_from ON user_from.id=from_id
			WHERE to_id = ?
			", array($user->id));
		$pms = array();
		foreach($arr as $pm) {
			$pms[] = new PM($pm);
		}
		return $pms;
	}
}
?>
