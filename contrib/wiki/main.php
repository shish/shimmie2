<?php
/**
 * Name: Simple Wiki
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: A simple wiki, for those who don't want the
 *              hugeness of mediawiki
 */

// WikiUpdateEvent {{{
class WikiUpdateEvent extends Event {
	var $user;
	var $page;

	public function WikiUpdateEvent($user, $page) {
		$this->user = $user;
		$this->page = $page;
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
// event handler {{{
	public function receive_event($event) {
		if(is_a($event, 'InitExtEvent')) {
			$this->setup();
		}

		if(is_a($event, 'PageRequestEvent') && ($event->page == "wiki")) {
			global $page;

			$page->add_block(new NavBlock());

			if(is_null($event->get_arg(0)) || strlen(trim($event->get_arg(0))) == 0) {
				$title = "Index";
			}
			else {
				$title = $event->get_arg(0);
			}
			
			$page->set_title(html_escape($title));
			$page->set_heading(html_escape($title));

			$content = $this->get_page($title);
			if(isset($_GET['save']) && $_GET['save'] == "on") {
				$title = $_POST['title'];
				$rev = int_escape($_POST['revision']);
				$body = $_POST['body'];
				$lock = isset($_POST['lock']) && ($_POST['lock'] == "on");
				
				global $user;
				if($this->can_edit($user, $this->get_page($title))) {
					if($user->is_admin()) {
						$this->set_page($title, $rev, $body, $lock);
					}
					else {
						$this->set_page($title, $rev, $body, false);
					}

					$u_title = url_escape($title);

					global $page;
					$page->set_mode("redirect");
					$page->set_redirect(make_link("wiki/$u_title"));
				}
				else {
					global $page;
					$page->set_title("Denied");
					$page->set_heading("Denied");
					$page->add_block(new NavBlock());
					$page->add_block(new Block("Denied", "You do not have permission to edit this page"));
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
					$content = $this->create_display_html($blank);
				}
				else {
					$content = $this->create_edit_html($blank);
				}
				$page->add_block(new Block("Content", $content));
			}
			else if(isset($_GET['edit']) && $_GET['edit'] == "on") {
				$page->add_block(new Block("Content", $this->create_edit_html($content)));
			}
			else {
				$page->add_block(new Block("Content", $this->create_display_html($content)));
			}
		}

		if(is_a($event, 'WikiUpdateEvent')) {
			$this->update_wiki_page($event->user, $event->page);
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Wiki");
			$sb->add_bool_option("wiki_edit_anon", "Allow anonymous edits: ");
			$sb->add_bool_option("wiki_edit_user", "<br>Allow user edits: ");
			$event->panel->add_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_bool_from_post("wiki_edit_anon");
			$event->config->set_bool_from_post("wiki_edit_user");
		}
	}
// }}}
// misc {{{
	private function can_edit($user, $page) {
		global $config;

		if(!is_null($page) && $page->is_locked() && !$user->is_admin()) return false;
		if($config->get_bool("wiki_edit_anon", false) && $user->is_anonymous()) return true;
		if($config->get_bool("wiki_edit_user", false) && !$user->is_anonymous()) return true;
		if($user->is_admin()) return true;
		return false;
	}
// }}}
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
	private function set_page($title, $rev, $body, $locked) {
		global $database;
		global $user;
		// FIXME: deal with collisions
		$row = $database->Execute("
				INSERT INTO wiki_pages(owner_id, owner_ip, date, title, revision, locked, body)
				VALUES (?, ?, now(), ?, ?, ?, ?)", array($user->id, $_SERVER['REMOTE_ADDR'], $title, $rev, $locked?'Y':'N', $body));
	}
// }}}
// html {{{
	private function create_edit_html($page) {
		$h_title = html_escape($page->title);
		$u_title = url_escape($page->title);
		$i_revision = int_escape($page->revision) + 1;

		global $user;
		if($user->is_admin()) {
			$val = $page->is_locked() ? " checked" : "";
			$lock = "<br>Lock page: <input type='checkbox' name='lock'$val>";
		}
		else {
			$lock = "";
		}
		return "
			<form action='".make_link("wiki/$u_title", "save=on")."' method='POST'>
				<input type='hidden' name='title' value='$h_title'>
				<input type='hidden' name='revision' value='$i_revision'>
				<textarea name='body' style='width: 100%' rows='20'>".html_escape($page->body)."</textarea>
				$lock
				<br><input type='submit' value='Save'>
			</form>
		";
	}

	private function create_display_html($page) {
		$owner = $page->get_owner();

		$tfe = new TextFormattingEvent($page->body);
		send_event($tfe);

		$html = "<div class='wiki-page'>";
		$html .= $tfe->formatted;
		$html .= "<hr>";
		$html .= "<p class='wiki-footer'>Revision {$page->revision} by ".
		         "<a href='".make_link("user/{$owner->name}")."'>{$owner->name}</a> at {$page->date} ";

		global $user;
		if($this->can_edit($user, $page)) {
			$html .= "[<a href='".make_link("wiki/{$page->title}", "edit=on")."'>edit</a>] ";
		}

		$html .= "</p></div>";

		return $html;
	}
// }}}
}
add_event_listener(new Wiki());
?>
