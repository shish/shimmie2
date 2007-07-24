<?php

// RemoveIPBanEvent {{{
class RemoveIPBanEvent extends Event {
	var $ip;

	public function RemoveIPBanEvent($ip) {
		$this->ip = $ip;
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
			if($config->get_int("ext_ipban_version") < 1) {
				$this->install();
			}
		}

		$this->check_ip_ban();

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
						$page->set_redirect(make_link("admin"));
					}
				}
				else if($event->get_arg(0) == "remove") {
					if(isset($_POST['ip'])) {
						send_event(new RemoveIPBanEvent($_POST['ip']));

						global $page;
						$page->set_mode("redirect");
						$page->set_redirect(make_link("admin"));
					}
				}
			}
		}

		if(is_a($event, 'AddIPBanEvent')) {
			$this->add_ip_ban($event->ip, $event->reason, $event->end);
		}

		if(is_a($event, 'RemoveIPBanEvent')) {
			$this->remove_ip_ban($event->ip);
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			global $page;
			$this->theme->display_bans($page, $this->get_bans());
		}
	}
// }}}
// installer {{{
	protected function install() {
		global $database;
		global $config;
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
// }}}
// deal with banned person {{{
	private function check_ip_ban() {
		$row = $this->get_ip_ban($_SERVER['REMOTE_ADDR']);
		if($row) {
			global $config;

			print "IP <b>{$row['ip']}</b> has been banned because of <b>{$row['reason']}</b>";

			$contact_link = $config->get_string("contact_link");
			if(!empty($contact_link)) {
				print "<p><a href='$contact_link'>Contact The Admin</a>";
			}
			exit;
		}
	}
// }}}
// database {{{
	public function get_bans() {
		// FIXME: many
		global $database;
		$bans = $database->db->GetAll("SELECT * FROM bans");
		if($bans) {return $bans;}
		else {return array();}
	}

	public function get_ip_ban($ip) {
		global $database;
		// yes, this is "? LIKE var", because ? is the thing with matching tokens
		return $database->db->GetRow("SELECT * FROM bans WHERE ? LIKE ip AND date < now() AND (end > now() OR isnull(end))", array($ip));
	}

	public function add_ip_ban($ip, $reason, $end) {
		global $database;
		$database->Execute(
				"INSERT INTO bans (ip, reason, date, end) VALUES (?, ?, now(), ?)",
				array($ip, $reason, $end));
	}

	public function remove_ip_ban($ip) {
		global $database;
		$database->Execute("DELETE FROM bans WHERE ip = ?", array($ip));
	}
// }}}
}
add_event_listener(new IPBan(), 10);
?>
