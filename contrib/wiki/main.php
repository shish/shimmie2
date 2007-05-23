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

class WikiPage { // {{{
	var $id;
	var $owner_id;
	var $owner_ip;
	var $date;
	var $title;
	var $revision;
	var $body;
	
	public function WikiPage($row=null) {
		if(!is_null($row)) {
			$this->id = $row['id'];
			$this->owner_id = $row['owner_id'];
			$this->owner_ip = $row['owner_ip'];
			$this->date = $row['date'];
			$this->title = $row['title'];
			$this->revision = $row['revision'];
			$this->body = $row['body'];
		}
	}

	public function get_owner() {
		global $database;
		return $database->get_user_by_id($this->owner_id);
	}
} // }}}

class Wiki extends Extension {
// event handler {{{
	public function receive_event($event) {
		if(is_a($event, 'InitExtEvent')) {
			global $config;
			if($config->get_int("ext_wiki_version") < 1) {
				$this->install();
			}
		}

		if(is_a($event, 'PageRequestEvent') && ($event->page == "wiki")) {
			global $page;

			$page->add_side_block(new NavBlock());

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
				$rev = $_POST['revision'];
				$body = $_POST['body'];
				
				$this->set_page($title, $rev, $body);

				$u_title = url_escape($title);

				global $page;
				$page->set_mode("redirect");
				$page->set_redirect(make_link("wiki/$u_title"));
			}
			else if(is_null($content)) {
				$blank = new WikiPage();
				$blank->title = $title;
				$page->add_main_block(new Block("Content", $this->create_edit_html($blank)));
			}
			else if(isset($_GET['edit']) && $_GET['edit'] == "on") {
				$page->add_main_block(new Block("Content", $this->create_edit_html($content)));
			}
			else {
				$page->add_main_block(new Block("Content", $this->create_display_html($content)));
			}
		}

		if(is_a($event, 'WikiUpdateEvent')) {
			$this->update_wiki_page($event->user, $event->page);
		}
	}
// }}}
// installer {{{
	protected function install() {
		global $database;
		global $config;
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
	private function set_page($title, $rev, $body) {
		global $database;
		global $user;
		// FIXME: deal with collisions
		$row = $database->Execute("
				INSERT INTO wiki_pages(owner_id, owner_ip, date, title, revision, body)
				VALUES (?, ?, now(), ?, ?, ?)", array($user->id, $_SERVER['REMOTE_ADDR'], $title, $rev, $body));
	}
// }}}
// html {{{
	private function create_edit_html($page) {
		$h_title = html_escape($page->title);
		$u_title = url_escape($page->title);
		$i_revision = int_escape($page->revision) + 1;

		return "
			<form action='".make_link("wiki/$u_title", "save=on")."' method='POST'>
				<input type='hidden' name='title' value='$h_title'>
				<input type='hidden' name='revision' value='$i_revision'>
				<textarea name='body' style='width: 100%' rows='20'>".html_escape($page->body)."</textarea>
				<br><input type='submit' value='Save'>
			</form>
		";
	}

	private function create_display_html($page) {
		$owner = $page->get_owner();

		$html = "";
		$html .= bbcode_to_html($page->body);
		$html .= "<hr>";
		$html .= "<p>Revision {$page->revision} by {$owner->name} at {$page->date} ";
		$html .= "[<a href='".make_link("wiki/{$page->title}", "edit=on")."'>edit</a>] ";

		return $html;
	}
// }}}
}
add_event_listener(new Wiki());
?>
