<?php
/**
 * Name: EventLog
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: A log of things that happen, for abuse tracking
 */

class EventLog extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("event_log", "EventLogTheme");

		if(is_a($event, 'InitExtEvent')) {
			$this->setup();
		}

		if(is_a($event, 'PageRequestEvent') && $event->page_name == "event_log") {
			global $user;
			global $database;
			if($user->is_admin()) {
				if(isset($_POST['action'])) {
					switch($_POST['action']) {
						case 'clear':
							$database->execute("DELETE FROM event_log");
							break;
					}
				}
				
				$sort = "date";
				if(isset($_GET['sort']) && in_array($_GET['sort'], array("name", "date", "ip", "event"))) {
					$sort = $_GET['sort'];
				}
				
				$order = "DESC";
				if(isset($_GET['order']) && in_array($_GET['order'], array("ASC", "DESC"))) {
					$order = $_GET['order'];
				}

				$events = $database->db->GetAll("
					SELECT event_log.*,users.name FROM event_log
					JOIN users ON event_log.owner_id = users.id
					WHERE date > date_sub(now(), interval 1 day)
					ORDER BY $sort $order
				");
				$this->theme->display_page($event->page, $events);
			}
			else {
				$this->theme->display_error($event->page, "Denied", "Only admins can see the event log");
			}
		}
		if(is_a($event, 'UserBlockBuildingEvent')) {
			if($event->user->is_admin()) {
				$event->add_link("Event Log", make_link("event_log"));
			}
		}

		global $user; // bad
		if(is_a($event, 'WikiUpdateEvent')) {
			$this->add_to_log($event->user, 'Wiki Update', "Edited '{$event->page->title}'");
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$this->add_to_log($user, 'Config Save', "Updated the board config");
		}
		if(is_a($event, 'ImageDeletionEvent')) {
			$this->add_to_log($user, 'Image Deletion', "Deleted image {$event->image->id} (tags: {$event->image->get_tag_list()})");
		}
		if(is_a($event, 'SourceSetEvent')) {
			$this->add_to_log($user, 'Source Set', "Source for image #{$event->image_id} set to '{$event->source}'");
		}
		if(is_a($event, 'TagSetEvent')) {
			$this->add_to_log($user, 'Tags Set', "Tags for image #{$event->image_id} set to '{$event->tags}'");
		}
	}

	private function add_to_log($user, $event, $entry) {
		global $database;
		
		$database->execute("
			INSERT INTO event_log (owner_id, owner_ip, date, event, entry)
			VALUES (?, ?, now(), ?, ?)",
			array($user->id, $_SERVER['REMOTE_ADDR'], $event, $entry));
	}

	private function setup() {
		global $database;
		global $config;

		if($config->get_int("ext_event_log_version", 0) < 1) {
			$database->Execute("CREATE TABLE event_log (
				id int(11) NOT NULL auto_increment primary key,
				owner_id int(11) NOT NULL,
				owner_ip char(15) NOT NULL,
				date datetime NOT NULL,
				event varchar(32) NOT NULL,
				entry varchar(255) NOT NULL
			)");
			$config->set_int("ext_event_log_version", 1);
		}
	}
}
add_event_listener(new EventLog());
?>
