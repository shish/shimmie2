<?php
/**
 * Name: IP Ban
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Ban IP addresses
 * Documentation:
 *  <b>Adding a Ban</b>
 *  <br>IP: Can be a single IP (eg. 123.234.210.21), or a CIDR block (eg. 152.23.43.0/24) 
 *  <br>Reason: Any text, for the admin to remember why the ban was put in place 
 *  <br>Until: Either a date in YYYY-MM-DD format, or an offset like "3 days"
 */

// RemoveIPBanEvent {{{
class RemoveIPBanEvent extends Event {
	var $id;

	public function RemoveIPBanEvent($id) {
		$this->id = $id;
	}
}
// }}}
// AddIPBanEvent {{{
class AddIPBanEvent extends Event {
	var $ip;
	var $reason;
	var $end;

	public function AddIPBanEvent($ip, $reason, $end) {
		$this->ip = $ip;
		$this->reason = $reason;
		$this->end = $end;
	}
}
// }}}

class IPBan implements Extension {
	var $theme;
// event handler {{{
	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			global $config;
			if($config->get_int("ext_ipban_version") < 5) {
				$this->install();
			}

			$this->check_ip_ban();
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("ip_ban")) {
			global $user;
			if($user->is_admin()) {
				if($event->get_arg(0) == "add") {
					if(isset($_POST['ip']) && isset($_POST['reason']) && isset($_POST['end'])) {
						if(empty($_POST['end'])) $end = null;
						else $end = $_POST['end'];
						send_event(new AddIPBanEvent($_POST['ip'], $_POST['reason'], $end));

						$event->page->set_mode("redirect");
						$event->page->set_redirect(make_link("ip_ban/list"));
					}
				}
				else if($event->get_arg(0) == "remove") {
					if(isset($_POST['id'])) {
						send_event(new RemoveIPBanEvent($_POST['id']));
						$event->page->set_mode("redirect");
						$event->page->set_redirect(make_link("ip_ban/list"));
					}
				}
				else if($event->get_arg(0) == "list") {
					$bans = (isset($_GET["all"])) ? $this->get_bans() : $this->get_active_bans();
					$this->theme->display_bans($event->page, $bans);
				}
			}
			else {
				$this->theme->display_permission_denied($event->page);
			}
		}

		if($event instanceof UserBlockBuildingEvent) {
			if($event->user->is_admin()) {
				$event->add_link("IP Bans", make_link("ip_ban/list"));
			}
		}

		if($event instanceof AddIPBanEvent) {
			global $user;
			$this->add_ip_ban($event->ip, $event->reason, $event->end, $user);
		}

		if($event instanceof RemoveIPBanEvent) {
			global $database;
			$database->Execute("DELETE FROM bans WHERE id = ?", array($event->id));
		}
	}
// }}}
// installer {{{
	protected function install() {
		global $database;
		global $config;

		// shortcut to latest
		if($config->get_int("ext_ipban_version") < 1) {
			$database->create_table("bans", "
				id SCORE_AIPK,
				banner_id INTEGER NOT NULL,
				ip SCORE_INET NOT NULL,
				end_timestamp INTEGER,
				reason TEXT NOT NULL,
				INDEX (end_timestamp)
			");
			$config->set_int("ext_ipban_version", 6);
		}

		// ===

		if($config->get_int("ext_ipban_version") < 1) {
			$database->Execute("CREATE TABLE bans (
				id int(11) NOT NULL auto_increment,
				ip char(15) default NULL,
				date datetime default NULL,
				end datetime default NULL,
				reason varchar(255) default NULL,
				PRIMARY KEY (id)
			)");
			$config->set_int("ext_ipban_version", 1);
		}

		if($config->get_int("ext_ipban_version") == 1) {
			$database->execute("ALTER TABLE bans ADD COLUMN banner_id INTEGER NOT NULL AFTER id");
			$config->set_int("ext_ipban_version", 2);
		}

		if($config->get_int("ext_ipban_version") == 2) {
			$database->execute("ALTER TABLE bans DROP COLUMN date");
			$database->execute("ALTER TABLE bans CHANGE ip ip CHAR(20) NOT NULL");
			$database->execute("ALTER TABLE bans CHANGE reason reason TEXT NOT NULL");
			$database->execute("CREATE INDEX bans__end ON bans(end)");
			$config->set_int("ext_ipban_version", 3);
		}

		if($config->get_int("ext_ipban_version") == 3) {
			$database->execute("ALTER TABLE bans CHANGE end old_end DATE NOT NULL");
			$database->execute("ALTER TABLE bans ADD COLUMN end INTEGER");
			$database->execute("UPDATE bans SET end = UNIX_TIMESTAMP(old_end)");
			$database->execute("ALTER TABLE bans DROP COLUMN old_end");
			$database->execute("CREATE INDEX bans__end ON bans(end)");
			$config->set_int("ext_ipban_version", 4);
		}

		if($config->get_int("ext_ipban_version") == 4) {
			$database->execute("ALTER TABLE bans CHANGE end end_timestamp INTEGER");
			$config->set_int("ext_ipban_version", 5);
		}

		if($config->get_int("ext_ipban_version") == 5) {
			$database->execute("ALTER TABLE bans CHANGE ip ip VARCHAR(15)");
			$config->set_int("ext_ipban_version", 6);
		}
	}
// }}}
// deal with banned person {{{
	private function check_ip_ban() {
		global $config;
		global $database;

		$remote = $_SERVER['REMOTE_ADDR'];
		$bans = $this->get_active_bans();
		foreach($bans as $row) {
			if(
				(strstr($row['ip'], '/') && ip_in_range($remote, $row['ip'])) ||
				($row['ip'] == $remote)
			) {
				$admin = User::by_id($config, $database, $row['banner_id']);
				$date = date("Y-m-d", $row['end_timestamp']);
				print "IP <b>{$row['ip']}</b> has been banned until <b>$date</b> by <b>{$admin->name}</b> because of <b>{$row['reason']}</b>";

				$contact_link = $config->get_string("contact_link");
				if(!empty($contact_link)) {
					print "<p><a href='$contact_link'>Contact The Admin</a>";
				}
				exit;
			}
		}
	}
// }}}
// database {{{
	private function get_bans() {
		global $database;
		$bans = $database->get_all("
			SELECT bans.*, users.name as banner_name
			FROM bans
			JOIN users ON banner_id = users.id
			ORDER BY end_timestamp, id
		");
		if($bans) {return $bans;}
		else {return array();}
	}

	private function get_active_bans() {
		global $database;

		$cached = $database->cache->get("bans");
		if($cached) return $cached;

		$bans = $database->get_all("
			SELECT bans.*, users.name as banner_name
			FROM bans
			JOIN users ON banner_id = users.id
			WHERE (end_timestamp > UNIX_TIMESTAMP(now())) OR (end_timestamp IS NULL)
			ORDER BY end_timestamp, id
		");

		$database->cache->set("bans", $bans);

		if($bans) {return $bans;}
		else {return array();}
	}

	private function add_ip_ban($ip, $reason, $end, $user) {
		global $database;
		$sql = "INSERT INTO bans (ip, reason, end_timestamp, banner_id) VALUES (?, ?, ?, ?)";
		$database->Execute($sql, array($ip, $reason, strtotime($end), $user->id));
		$database->cache->delete("bans");
	}
// }}}
}
add_event_listener(new IPBan(), 10);
?>
