<?php
/**
 * Name: Simple Wiki
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: A simple wiki, for those who don't want the hugeness of mediawiki
 */

// WikiUpdateEvent {{{
class WikiUpdateEvent extends Event {
	var $user;
	var $wikipage;

	public function WikiUpdateEvent($user, $wikipage) {
		$this->user = $user;
		$this->wikipage = $wikipage;
	}
}
// }}}
// WikiPage {{{
class WikiPage {
	var $id;
	var $owner_id;
	var $owner_ip;
	var $date;
	var $title;
	var $revision;
	var $locked;
	var $body;
	
	public function WikiPage($row=null) {
		if(!is_null($row)) {
			$this->id = $row['id'];
			$this->owner_id = $row['owner_id'];
			$this->owner_ip = $row['owner_ip'];
			$this->date = $row['date'];
			$this->title = $row['title'];
			$this->revision = $row['revision'];
			$this->locked = ($row['locked'] == 'Y');
			$this->body = $row['body'];
		}
	}

	public function get_owner() {
		global $database;
		return $database->get_user_by_id($this->owner_id);
	}

	public function is_locked() {
		return $this->locked;
	}
}
// }}}
class Wiki extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("wiki", "WikiTheme");

		if(is_a($event, 'InitExtEvent')) {
			$this->setup();
		}

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "wiki")) {
			if(is_null($event->get_arg(0)) || strlen(trim($event->get_arg(0))) == 0) {
				$title = "Index";
			}
			else {
				$title = $event->get_arg(0);
			}
			
			$content = $this->get_page($title);

			if(isset($_GET['save']) && $_GET['save'] == "on") {
				$title = $_POST['title'];
				$rev = int_escape($_POST['revision']);
				$body = $_POST['body'];
				$lock = isset($_POST['lock']) && ($_POST['lock'] == "on");
				
				global $user;
				if($this->can_edit($user, $this->get_page($title))) {
					$wikipage = new WikiPage();
					$wikipage->title = $title;
					$wikipage->rev = $rev;
					$wikipage->body = $body;
					$wikipage->lock = $user->is_admin() ? $lock : false;
					send_event(new WikiUpdateEvent($user, $wikipage));

					$u_title = url_escape($title);

					$event->page->set_mode("redirect");
					$event->page->set_redirect(make_link("wiki/$u_title"));
				}
				else {
					$this->theme->display_error($event->page, "Denied", "You do not have permission to edit this page");
				}
			}
			else if(is_null($content)) {
				$default = $this->get_page("wiki:default");
				$blank = new WikiPage();
				$blank->title = $title;
				if(!is_null($default) && !isset($_GET['edit'])) {
					global $config;
					$blank->body = $default->body;
					$blank->owner_id = $config->get_int('anon_id');
					$blank->date = $default->date;
					$this->theme->display_page($event->page, $blank, $this->get_page("wiki:sidebar"));
				}
				else {
					$this->theme->display_page_editor($event->page, $blank);
				}
			}
			else if(isset($_GET['edit']) && $_GET['edit'] == "on") {
				$this->theme->display_page_editor($event->page, $content);
			}
			else {
				$this->theme->display_page($event->page, $content, $this->get_page("wiki:sidebar"));
			}
		}

		if(is_a($event, 'WikiUpdateEvent')) {
			$this->set_page($event->user, $event->wikipage);
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Wiki");
			$sb->add_bool_option("wiki_edit_anon", "Allow anonymous edits: ");
			$sb->add_bool_option("wiki_edit_user", "<br>Allow user edits: ");
			$event->panel->add_block($sb);
		}
	}

	private function can_edit($user, $page) {
		global $config;

		if(!is_null($page) && $page->is_locked() && !$user->is_admin()) return false;
		if($config->get_bool("wiki_edit_anon", false) && $user->is_anonymous()) return true;
		if($config->get_bool("wiki_edit_user", false) && !$user->is_anonymous()) return true;
		if($user->is_admin()) return true;
		return false;
	}

// installer {{{
	private function setup() {
		global $database;
		global $config;

		if($config->get_int("ext_wiki_version", 0) < 1) {
			$database->Execute("CREATE TABLE wiki_pages (
				id int(11) NOT NULL auto_increment,
				owner_id int(11) NOT NULL,
				owner_ip char(15) NOT NULL,
				date datetime default NULL,
				title varchar(255) NOT NULL,
				revision int(11) NOT NULL default 1,
				body text NOT NULL,
				PRIMARY KEY (id), UNIQUE (title, revision)
			)");
			$config->set_int("ext_wiki_version", 1);
		}
		if($config->get_int("ext_wiki_version") < 2) {
			$database->Execute("ALTER TABLE wiki_pages ADD COLUMN
				locked ENUM('Y', 'N') DEFAULT 'N' NOT NULL AFTER REVISION");
			$config->set_int("ext_wiki_version", 2);
		}
	}
// }}}
// database {{{
	private function get_page($title, $revision=-1) {
		global $database;
		$row = $database->db->GetRow("
				SELECT *
				FROM wiki_pages
				WHERE title LIKE ?
				ORDER BY revision DESC", array($title));
		return ($row ? new WikiPage($row) : null);
	}

	// TODO: accept a WikiPage object
	private function set_page($user, $wpage) {
		global $database;
		// FIXME: deal with collisions
		$row = $database->Execute("
				INSERT INTO wiki_pages(owner_id, owner_ip, date, title, revision, locked, body)
				VALUES (?, ?, now(), ?, ?, ?, ?)", array($user->id, $_SERVER['REMOTE_ADDR'],
				$wpage->title, $wpage->rev, $wpage->locked?'Y':'N', $wpage->body));
	}
// }}}
}
add_event_listener(new Wiki());
?>
