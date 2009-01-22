<?php
/**
 * Name: EventLog
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: A log of things that happen, for abuse tracking
 */

class EventLog implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			$this->setup();
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("event_log")) {
			global $database;
			if($event->user->is_admin()) {
				if(isset($_POST['action'])) {
					switch($_POST['action']) {
						case 'clear':
							$database->execute("DELETE FROM event_log");
							break;
					}
				}

				$columns = array("name", "date", "owner_ip", "event");
				$orders = array("ASC", "DESC");

				$sort = "date";
				if(isset($_GET['sort']) && in_array($_GET['sort'], $columns)) {
					$sort = $_GET['sort'];
				}

				$order = "DESC";
				if(isset($_GET['order']) && in_array($_GET['order'], $orders)) {
					$order = $_GET['order'];
				}

				$filter_sql = "";
				if(isset($_GET['filter']) && isset($_GET['where']) && in_array($_GET['filter'], $columns)) {
					$filter = $_GET['filter'];
					$where = $database->db->Quote($_GET['where']);
					$filter_sql = "WHERE $filter = $where";
				}

				$events = $database->get_all("
					SELECT event_log.*,users.name FROM event_log
					JOIN users ON event_log.owner_id = users.id
					$filter_sql
					ORDER BY $sort $order
				");
				$this->theme->display_page($event->page, $events);
			}
			else {
				$this->theme->display_permission_denied($event->page);
			}
		}
		if($event instanceof UserBlockBuildingEvent) {
			if($event->user->is_admin()) {
				$event->add_link("Event Log", make_link("event_log"));
			}
		}

		global $user; // bad
		if($event instanceof UploadingImageEvent) {
			$this->add_to_log($event->user, 'Uploading Image', "Uploaded a new image");
		}
		if($event instanceof CommentPostingEvent) {
			$this->add_to_log($event->user, 'Comment Posting', "Posted a comment on image #{$event->image_id}");
		}
		if($event instanceof WikiUpdateEvent) {
			$this->add_to_log($event->user, 'Wiki Update', "Edited '{$event->wikipage->title}'");
		}
		if($event instanceof ConfigSaveEvent) {
			$this->add_to_log($user, 'Config Save', "Updated the board config");
		}
		if($event instanceof ImageDeletionEvent) {
			$this->add_to_log($user, 'Image Deletion', "Deleted image {$event->image->id} (tags: {$event->image->get_tag_list()})");
		}
		if($event instanceof SourceSetEvent) {
			$this->add_to_log($user, 'Source Set', "Source for image #{$event->image_id} set to '{$event->source}'");
		}
		if($event instanceof TagSetEvent) {
			$tags = implode($event->tags, ", ");
			$this->add_to_log($user, 'Tags Set', "Tags for image #{$event->image_id} set to '$tags'");
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
			$database->create_table("event_log", "
				id SCORE_AIPK,
				owner_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
				owner_ip SCORE_INET NOT NULL,
				date DATETIME NOT NULL,
				event VARCHAR(32) NOT NULL,
				entry TEXT NOT NULL
			");
			$config->set_int("ext_event_log_version", 1);
		}
	}
}
add_event_listener(new EventLog(), 99); // ignore vetoed events
?>
