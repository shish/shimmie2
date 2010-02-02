<?php
/**
 * Name: Alias Editor
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Edit the alias list
 * Documentation:
 *  The list is visible at <a href="$site/alias/list">/alias/list</a>; only
 *  site admins can edit it, other people can view and download it
 */

class AddAliasEvent extends Event {
	var $oldtag;
	var $newtag;

	public function AddAliasEvent($oldtag, $newtag) {
		$this->oldtag = $oldtag;
		$this->newtag = $newtag;
	}
}

class AddAliasException extends SCoreException {}

class AliasEditor implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("alias")) {
			if($event->get_arg(0) == "add") {
				if($user->is_admin()) {
					if(isset($_POST['oldtag']) && isset($_POST['newtag'])) {
						try {
							$aae = new AddAliasEvent($_POST['oldtag'], $_POST['newtag']);
							send_event($aae);
							$page->set_mode("redirect");
							$page->set_redirect(make_link("alias/list"));
						}
						catch(AddAliasException $ex) {
							$this->theme->display_error($page, "Error adding alias", $ex->getMessage());
						}
					}
				}
			}
			else if($event->get_arg(0) == "remove") {
				if($user->is_admin()) {
					if(isset($_POST['oldtag'])) {
						$database->Execute("DELETE FROM aliases WHERE oldtag=?", array($_POST['oldtag']));
						log_info("alias_editor", "Deleted alias for ".$_POST['oldtag']);

						$page->set_mode("redirect");
						$page->set_redirect(make_link("alias/list"));
					}
				}
			}
			else if($event->get_arg(0) == "list") {
				$page_number = $event->get_arg(1);
				if(is_null($page_number) || !is_numeric($page_number)) {
					$page_number = 0;
				}
				else if ($page_number <= 0) {
					$page_number = 0;
				}
				else {
					$page_number--;
				}

				$alias_per_page = $config->get_int('alias_items_per_page', 30);

				if($database->engine->name == "mysql") {
					$query = "SELECT oldtag, newtag FROM aliases ORDER BY newtag ASC LIMIT ?, ?";
				}
				else {
					$query = "SELECT oldtag, newtag FROM aliases ORDER BY newtag ASC OFFSET ? LIMIT ?";
				}
				$alias = $database->db->GetAssoc($query,
					array($page_number * $alias_per_page, $alias_per_page)
				);

				$total_pages = ceil($database->db->GetOne("SELECT COUNT(*) FROM aliases") / $alias_per_page);

				$this->theme->display_aliases($page, $alias, $user->is_admin(), $page_number + 1, $total_pages);
			}
			else if($event->get_arg(0) == "export") {
				$page->set_mode("data");
				$page->set_type("text/plain");
				$page->set_data($this->get_alias_csv($database));
			}
			else if($event->get_arg(0) == "import") {
				if($user->is_admin()) {
					print_r($_FILES);
					if(count($_FILES) > 0) {
						global $database;
						$tmp = $_FILES['alias_file']['tmp_name'];
						$contents = file_get_contents($tmp);
						$this->add_alias_csv($database, $contents);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("alias/list"));
					}
					else {
						$this->theme->display_error($page, "No File Specified", "You have to upload a file");
					}
				}
				else {
					$this->theme->display_error($page, "Admins Only", "Only admins can edit the alias list");
				}
			}
		}

		if($event instanceof AddAliasEvent) {
			global $database;
			$pair = array($event->oldtag, $event->newtag);
			if($database->db->GetRow("SELECT * FROM aliases WHERE oldtag=? AND lower(newtag)=lower(?)", $pair)) {
				throw new AddAliasException("That alias already exists");
			}
			else {
				$database->Execute("INSERT INTO aliases(oldtag, newtag) VALUES(?, ?)", $pair);
				log_info("alias_editor", "Added alias for {$event->oldtag} -> {$event->newtag}");
			}
		}

		if($event instanceof UserBlockBuildingEvent) {
			if($user->is_admin()) {
				$event->add_link("Alias Editor", make_link("alias/list"));
			}
		}
	}

	private function get_alias_csv($database) {
		$csv = "";
		$aliases = $database->db->GetAssoc("SELECT oldtag, newtag FROM aliases");
		foreach($aliases as $old => $new) {
			$csv .= "$old,$new\n";
		}
		return $csv;
	}

	private function add_alias_csv($database, $csv) {
		foreach(explode("\n", $csv) as $line) {
			$parts = explode(",", $line);
			if(count($parts) == 2) {
				$database->execute("INSERT INTO aliases(oldtag, newtag) VALUES(?, ?)", $parts);
			}
		}
	}
}
add_event_listener(new AliasEditor());
?>
