<?php
/**
 * Name: Simple Wiki
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: A simple wiki, for those who don't want the hugeness of mediawiki
 */

class WikiUpdateEvent extends Event {
	var $user;
	var $wikipage;

	public function WikiUpdateEvent(User $user, WikiPage $wikipage) {
		$this->user = $user;
		$this->wikipage = $wikipage;
	}
}

class WikiUpdateException extends SCoreException {
}

class WikiPage {
	var $id;
	var $owner_id;
	var $owner_ip;
	var $date;
	var $title;
	var $revision;
	var $locked;
	var $body;

	public function WikiPage($row) {
		assert(!empty($row));

		$this->id = $row['id'];
		$this->owner_id = $row['owner_id'];
		$this->owner_ip = $row['owner_ip'];
		$this->date = $row['date'];
		$this->title = $row['title'];
		$this->revision = $row['revision'];
		$this->locked = ($row['locked'] == 'Y');
		$this->body = $row['body'];
	}

	public function get_owner() {
		return User::by_id($this->owner_id);
	}

	public function is_locked() {
		return $this->locked;
	}
}

class Wiki extends SimpleExtension {
	public function onInitExt($event) {
		global $database;
		global $config;

		if($config->get_int("ext_wiki_version", 0) < 1) {
			$database->create_table("wiki_pages", "
				id SCORE_AIPK,
				owner_id INTEGER NOT NULL,
				owner_ip SCORE_INET NOT NULL,
				date DATETIME DEFAULT NULL,
				title VARCHAR(255) NOT NULL,
				revision INTEGER NOT NULL DEFAULT 1,
				locked SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
				body TEXT NOT NULL,
				UNIQUE (title, revision),
				FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
			");
			$config->set_int("ext_wiki_version", 2);
		}
		if($config->get_int("ext_wiki_version") < 2) {
			$database->Execute("ALTER TABLE wiki_pages ADD COLUMN
				locked ENUM('Y', 'N') DEFAULT 'N' NOT NULL AFTER REVISION");
			$config->set_int("ext_wiki_version", 2);
		}
	}

	public function onPageRequest($event) {
		global $config, $page, $user;
		if($event->page_matches("wiki")) {
			if(is_null($event->get_arg(0)) || strlen(trim($event->get_arg(0))) == 0) {
				$title = "Index";
			}
			else {
				$title = $event->get_arg(0);
			}

			$content = $this->get_page($title);
			$this->theme->display_page($page, $content, $this->get_page("wiki:sidebar"));
		}
		else if($event->page_matches("wiki_admin/edit")) {
			$content = $this->get_page($_POST['title']);
			$this->theme->display_page_editor($page, $content);
		}
		else if($event->page_matches("wiki_admin/save")) {
			$title = $_POST['title'];
			$rev = int_escape($_POST['revision']);
			$body = $_POST['body'];
			$lock = $user->is_admin() && isset($_POST['lock']) && ($_POST['lock'] == "on");

			if($this->can_edit($user, $this->get_page($title))) {
				$wikipage = $this->get_page($title);
				$wikipage->rev = $rev;
				$wikipage->body = $body;
				$wikipage->locked = $lock;
				send_event(new WikiUpdateEvent($user, $wikipage));

				$u_title = url_escape($title);
				$page->set_mode("redirect");
				$page->set_redirect(make_link("wiki/$u_title"));
			}
			else {
				$this->theme->display_permission_denied($page);
			}
		}
		else if($event->page_matches("wiki_admin/delete_revision")) {
			if($user->is_admin()) {
				global $database;
				$database->Execute(
						"DELETE FROM wiki_pages WHERE title=? AND revision=?",
						array($_POST["title"], $_POST["revision"]));
				$u_title = url_escape($_POST["title"]);
				$page->set_mode("redirect");
				$page->set_redirect(make_link("wiki/$u_title"));
			}
		}
		else if($event->page_matches("wiki_admin/delete_all")) {
			if($user->is_admin()) {
				global $database;
				$database->Execute(
						"DELETE FROM wiki_pages WHERE title=?",
						array($_POST["title"]));
				$u_title = url_escape($_POST["title"]);
				$page->set_mode("redirect");
				$page->set_redirect(make_link("wiki/$u_title"));
			}
		}
	}

	public function onWikiUpdate($event) {
		global $database;
		$wpage = $event->wikipage;
		try {
			$row = $database->Execute("
				INSERT INTO wiki_pages(owner_id, owner_ip, date, title, revision, locked, body)
				VALUES (?, ?, now(), ?, ?, ?, ?)", array($event->user->id, $_SERVER['REMOTE_ADDR'],
				$wpage->title, $wpage->rev, $wpage->locked?'Y':'N', $wpage->body));
		}
		catch(Exception $e) {
			throw new WikiUpdateException("Somebody else edited that page at the same time :-(");
		}
	}

	public function onSetupBuilding($event) {
		$sb = new SetupBlock("Wiki");
		$sb->add_bool_option("wiki_edit_anon", "Allow anonymous edits: ");
		$sb->add_bool_option("wiki_edit_user", "<br>Allow user edits: ");
		$event->panel->add_block($sb);
	}

	/**
	 * See if the given user is allowed to edit the given page
	 *
	 * @retval boolean
	 */
	public static function can_edit(User $user, WikiPage $page) {
		global $config;

		// admins can edit everything
		if($user->is_admin()) return true;

		// anon / user can't ever edit locked pages
		if($page->is_locked()) return false;

		// anon / user can edit if allowed by config
		if($config->get_bool("wiki_edit_anon", false) && $user->is_anonymous()) return true;
		if($config->get_bool("wiki_edit_user", false) && !$user->is_anonymous()) return true;

		return false;
	}

	private function get_page($title, $revision=-1) {
		global $database;
		// first try and get the actual page
		$row = $database->db->GetRow("
				SELECT *
				FROM wiki_pages
				WHERE title LIKE ?
				ORDER BY revision DESC", array($title));

		// fall back to wiki:default
		if(empty($row)) {
			$row = $database->db->GetRow("
					SELECT *
					FROM wiki_pages
					WHERE title LIKE ?
					ORDER BY revision DESC", "wiki:default");

			// fall further back to manual
			if(empty($row)) {
				$row = array(
					"id" => -1,
					"owner_ip" => "0.0.0.0",
					"date" => "",
					"revision" => 0,
					"locked" => false,
					"body" => "This is a default page for when a page is empty, ".
						"it can be edited by editing [[wiki:default]].",
				);
			}

			// correct the default
			global $config;
			$row["title"] = $title;
			$row["owner_id"] = $config->get_int("anon_id", 0);
		}

		assert(!empty($row));

		return new WikiPage($row);
	}
}
?>
