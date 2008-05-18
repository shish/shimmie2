<?php
/**
 * Name: IP Ban
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Ban IP addresses
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

class IPBan extends Extension {
	var $theme;
// event handler {{{
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("ipban", "IPBanTheme");

		if(is_a($event, 'InitExtEvent')) {
			global $config;
			if($config->get_int("ext_ipban_version") < 4) {
				$this->install();
			}
			
			$this->check_ip_ban();
		}

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "ip_ban")) {
			global $user;
			if($user->is_admin()) {
				if($event->get_arg(0) == "add") {
					if(isset($_POST['ip']) && isset($_POST['reason']) && isset($_POST['end'])) {
						if(empty($_POST['end'])) $end = null;
						else $end = $_POST['end'];
						send_event(new AddIPBanEvent($_POST['ip'], $_POST['reason'], $end));

						global $page;
						$page->set_mode("redirect");
						$page->set_redirect(make_link("ip_ban/list"));
					}
				}
				else if($event->get_arg(0) == "remove") {
					if(isset($_POST['id'])) {
						send_event(new RemoveIPBanEvent($_POST['id']));

						global $page;
						$page->set_mode("redirect");
						$page->set_redirect(make_link("ip_ban/list"));
					}
				}
				else if($event->get_arg(0) == "list") {
					global $page;
					$this->theme->display_bans($page, $this->get_bans());
				}
			}
		}

		if(is_a($event, 'UserBlockBuildingEvent')) {
			if($event->user->is_admin()) {
				$event->add_link("IP Bans", make_link("ip_ban/list"));
			}
		}

		if(is_a($event, 'AddIPBanEvent')) {
			global $user;
			$this->add_ip_ban($event->ip, $event->reason, $event->end, $user);
		}

		if(is_a($event, 'RemoveIPBanEvent')) {
			$this->remove_ip_ban($event->id);
		}
	}
// }}}
// installer {{{
	protected function install() {
		global $database;
		global $config;
		
		// shortcut to latest
		if($config->get_int("ext_ipban_version") < 1) {
			$database->execute("
				CREATE TABLE bans (
					id {$database->engine->auto_increment},
					banner_id INTEGER NOT NULL,
					ip CHAR(15) NOT NULL,
					end INTEGER,
					reason TEXT NOT NULL,
					INDEX (end)
				) {$database->engine->create_table_extras};
			");
			$config->set_int("ext_ipban_version", 4);
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
				$admin = $database->get_user_by_id($row['banner_id']);
				print "IP <b>{$row['ip']}</b> has been banned by <b>{$admin->name}</b> because of <b>{$row['reason']}</b>";

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
		$bans = $database->get_all("SELECT * FROM bans ORDER BY end, id");
		if($bans) {return $bans;}
		else {return array();}
	}

	private function get_active_bans() {
		global $database;
		$bans = $database->get_all("SELECT * FROM bans WHERE (end > ? OR isnull(end))", array(time()));
		if($bans) {return $bans;}
		else {return array();}
	}

	private function add_ip_ban($ip, $reason, $end, $user) {
		global $database;
		$parts = array();
		if(preg_match("/^(\d+) (day|week|month)s?$/i", $end, $parts)) {
			$sql = "INSERT INTO bans (ip, reason, end, banner_id)
			        VALUES (?, ?, ?, ?)";
			$database->Execute($sql, array($ip, $reason, strtotime("+{$parts[1]} {$parts[2]}"), $user->id));
		}
		else {
			$sql = "INSERT INTO bans (ip, reason, banner_id)
			        VALUES (?, ?, ?)";
			$database->Execute($sql, array($ip, $reason, $user->id));
		}
	}

	private function remove_ip_ban($id) {
		global $database;
		$database->Execute("DELETE FROM bans WHERE id = ?", array($id));
	}
// }}}
}
add_event_listener(new IPBan(), 10);
?>
