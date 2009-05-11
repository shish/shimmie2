<?php

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

						$page->set_mode("redirect");
						$page->set_redirect(make_link("alias/list"));
					}
				}
			}
			else if($event->get_arg(0) == "list") {
				$this->theme->display_aliases($page,
						$database->db->GetAssoc("SELECT oldtag, newtag FROM aliases ORDER BY newtag"),
						$user->is_admin());
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
