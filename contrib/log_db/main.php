<?php
/*
 * Name: Logging (Database)
 * Author: Shish
 * Description: Keep a record of SCore events
 * Visibility: admin
 */

class LogDatabase extends SimpleExtension {
	public function onInitExt($event) {
		global $database;
		global $config;

		if($config->get_int("ext_log_database_version") < 1) {
			$database->create_table("score_log", "
				id SCORE_AIPK,
				date_sent SCORE_DATETIME NOT NULL,
				section VARCHAR(32) NOT NULL,
				username VARCHAR(32) NOT NULL,
				address SCORE_INET NOT NULL,
				priority INT NOT NULL,
				message TEXT NOT NULL
			");
				//INDEX(section)
			$config->set_int("ext_log_database_version", 1);
		}

		$config->set_default_int("log_db_priority", SCORE_LOG_INFO);
	}

	public function onSetupBuilding($event) {
		$sb = new SetupBlock("Logging (Database)");
		$sb->add_choice_option("log_db_priority", array(
			"Debug" => SCORE_LOG_DEBUG,
			"Info" => SCORE_LOG_INFO,
			"Warning" => SCORE_LOG_WARNING,
			"Error" => SCORE_LOG_ERROR,
			"Critical" => SCORE_LOG_CRITICAL,
		), "Debug Level: ");
		$event->panel->add_block($sb);
	}

	public function onPageRequest($event) {
		global $database, $user;
		if($event->page_matches("log/view")) {
			if($user->is_admin()) {
				$wheres = array();
				$args = array();
				if(!empty($_GET["time"])) {
					$wheres[] = "date_sent LIKE ?";
					$args[] = $_GET["time"]."%";
				}
				if(!empty($_GET["module"])) {
					$wheres[] = "section = ?";
					$args[] = $_GET["module"];
				}
				if(!empty($_GET["user"])) {
					if($database->engine->name == "pgsql") {
						if(preg_match("#\d+\.\d+\.\d+\.\d+(/\d+)?#", $_GET["user"])) {
							$wheres[] = "(username = ? OR address << ?)";
							$args[] = $_GET["user"];
							$args[] = $_GET["user"];
						}
						else {
							$wheres[] = "lower(username) = lower(?)";
							$args[] = $_GET["user"];
						}
					}
					else {
						$wheres[] = "(username = ? OR address = ?)";
						$args[] = $_GET["user"];
						$args[] = $_GET["user"];
					}
				}
				if(!empty($_GET["priority"])) {
					$wheres[] = "priority >= ?";
					$args[] = int_escape($_GET["priority"]);
				}
				$where = "";
				if(count($wheres) > 0) {
					$where = "WHERE ";
					$where .= join(" AND ", $wheres);
				}
				$events = $database->get_all("SELECT * FROM score_log $where ORDER BY id DESC LIMIT 50", $args);
				$this->theme->display_events($events);
			}
		}
	}

	public function onUserBlockBuilding($event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("Event Log", make_link("log/view"));
		}
	}

	public function onLog($event) {
		global $config, $database, $user;
		if($event->priority >= $config->get_int("log_db_priority")) {
			$database->execute("
				INSERT INTO score_log(date_sent, section, priority, username, address, message)
				VALUES(now(), ?, ?, ?, ?, ?)
			", array($event->section, $event->priority, $user->name, $_SERVER['REMOTE_ADDR'], $event->message));
		}
	}
}
?>
