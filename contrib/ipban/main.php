<?php
/*
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

class IPBan extends SimpleExtension {
	public function get_priority() {return 10;}

	public function onInitExt($event) {
		global $config;
		if($config->get_int("ext_ipban_version") < 5) {
			$this->install();
		}
		$this->check_ip_ban();
	}

	public function onPageRequest($event) {
		if($event->page_matches("ip_ban")) {
			global $config, $database, $page, $user;
			if($user->is_admin()) {
				if($event->get_arg(0) == "add" && $user->check_auth_token()) {
					if(isset($_POST['ip']) && isset($_POST['reason']) && isset($_POST['end'])) {
						if(empty($_POST['end'])) $end = null;
						else $end = $_POST['end'];
						send_event(new AddIPBanEvent($_POST['ip'], $_POST['reason'], $end));

						$page->set_mode("redirect");
						$page->set_redirect(make_link("ip_ban/list"));
					}
				}
				else if($event->get_arg(0) == "remove" && $user->check_auth_token()) {
					if(isset($_POST['id'])) {
						send_event(new RemoveIPBanEvent($_POST['id']));
						$page->set_mode("redirect");
						$page->set_redirect(make_link("ip_ban/list"));
					}
				}
				else if($event->get_arg(0) == "list") {
					$bans = (isset($_GET["all"])) ? $this->get_bans() : $this->get_active_bans();
					$this->theme->display_bans($page, $bans);
				}
			}
			else {
				$this->theme->display_permission_denied($page);
			}
		}
	}

	public function onUserBlockBuilding($event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("IP Bans", make_link("ip_ban/list"));
		}
	}

	public function onAddIPBan($event) {
		global $user;
		$this->add_ip_ban($event->ip, $event->reason, $event->end, $user);
	}

	public function onRemoveIPBan($event) {
		global $database;
		$database->Execute("DELETE FROM bans WHERE id = :id", array("id"=>$event->id));
		$database->cache->delete("ip_bans");
	}

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
		$remote = $_SERVER['REMOTE_ADDR'];
		$bans = $this->get_active_bans_sorted();

		// bans[0] = IPs
		if(isset($bans[0][$remote])) {
			$this->block($remote);  // never returns
		}

		// bans[1] = CIDR nets
		foreach($bans[1] as $ip => $true) {
			if(ip_in_range($remote, $ip)) {
				$this->block($remote);  // never returns
			}
		}
	}

	private function block($remote) {
		global $config, $database;

		$prefix = ($database->engine->name == "sqlite" ? "bans." : "");

		$bans = $this->get_active_bans();

		foreach($bans as $row) {
			$ip = $row[$prefix."ip"];
			if(
				(strstr($ip, '/') && ip_in_range($remote, $ip)) ||
				($ip == $remote)
			) {
				$reason = $row[$prefix.'reason'];
				$admin = User::by_id($row[$prefix.'banner_id']);
				$date = date("Y-m-d", $row[$prefix.'end_timestamp']);
				print "IP <b>$ip</b> has been banned until <b>$date</b> by <b>{$admin->name}</b> because of <b>$reason</b>";

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
			ORDER BY end_timestamp, bans.id
		");
		if($bans) {return $bans;}
		else {return array();}
	}

	private function get_active_bans() {
		global $database;

		$bans = $database->get_all("
			SELECT bans.*, users.name as banner_name
			FROM bans
			JOIN users ON banner_id = users.id
			WHERE (end_timestamp > :end_timestamp) OR (end_timestamp IS NULL)
			ORDER BY end_timestamp, bans.id
		", array("end_timestamp"=>time()));

		if($bans) {return $bans;}
		else {return array();}
	}

	// returns [ips, nets]
	private function get_active_bans_sorted() {
		global $database;

		$cached = $database->cache->get("ip_bans_sorted");
		if($cached) return $cached;

		$bans = $this->get_active_bans();
		$ips = array("0.0.0.0" => false);
		$nets = array("0.0.0.0/32" => false);
		foreach($bans as $row) {
			if(strstr($row['ip'], '/')) {
				$nets[$row['ip']] = true;
			}
			else {
				$ips[$row['ip']] = true;
			}
		}

		$sorted = array($ips, $nets);
		$database->cache->set("ip_bans_sorted", $sorted, 600);
		return $sorted;
	}

	private function add_ip_ban($ip, $reason, $end, $user) {
		global $database;
		$sql = "INSERT INTO bans (ip, reason, end_timestamp, banner_id) VALUES (:ip, :reason, :end, :admin_id)";
		$database->Execute($sql, array("ip"=>$ip, "reason"=>$reason, "end"=>strtotime($end), "admin_id"=>$user->id));
		$database->cache->delete("ip_bans");
		log_info("ipban", "'$user->name' has banned '$ip' because '$reason' until '$end'");
	}
// }}}
}
?>
