<?php

class AddAliasEvent extends Event {
	var $oldtag;
	var $newtag;

	public function AddAliasEvent($oldtag, $newtag) {
		$this->oldtag = $oldtag;
		$this->newtag = $newtag;
	}
}

class AliasEditor extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("alias_editor", "AliasEditorTheme");

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "alias")) {
			if($event->get_arg(0) == "add") {
				if($event->user->is_admin()) {
					if(isset($_POST['oldtag']) && isset($_POST['newtag'])) {
						$aae = new AddAliasEvent($_POST['oldtag'], $_POST['newtag']);
						send_event($aae);
						if($aae->vetoed) {
							$this->theme->display_error($event->page, "Error adding alias", $aae->veto_reason);
						}
						else {
							$event->page->set_mode("redirect");
							$event->page->set_redirect(make_link("alias/list"));
						}
					}
				}
			}
			else if($event->get_arg(0) == "remove") {
				if($event->user->is_admin()) {
					if(isset($_POST['oldtag'])) {
						global $database;
						$database->Execute("DELETE FROM aliases WHERE oldtag=?", array($_POST['oldtag']));

						$event->page->set_mode("redirect");
						$event->page->set_redirect(make_link("alias/list"));
					}
				}
			}
			else if($event->get_arg(0) == "list") {
				global $database;
				$this->theme->display_aliases($event->page, 
						$database->db->GetAssoc("SELECT oldtag, newtag FROM aliases ORDER BY newtag"),
						$event->user->is_admin());
			}
			else if($event->get_arg(0) == "export") {
				global $database;
				$event->page->set_mode("data");
				$event->page->set_type("text/plain");
				$event->page->set_data($this->get_alias_csv($database));
			}
			else if($event->get_arg(0) == "import") {
				if($event->user->is_admin()) {
					print_r($_FILES);
					if(count($_FILES) > 0) {
						global $database;
						$tmp = $_FILES['alias_file']['tmp_name'];
						$contents = file_get_contents($tmp);
						$this->add_alias_csv($database, $contents);
						$event->page->set_mode("redirect");
						$event->page->set_redirect(make_link("alias/list"));
					}
					else {
						$this->theme->display_error($event->page, "No File Specified", "You have to upload a file");
					}
				}
				else {
					$this->theme->display_error($event->page, "Admins Only", "Only admins can edit the alias list");
				}
			}
		}

		if(is_a($event, 'AddAliasEvent')) {
			global $database;
			$pair = array($event->oldtag, $event->newtag);
			if($database->db->GetRow("SELECT * FROM aliases WHERE oldtag=? AND lower(newtag)=lower(?)", $pair)) {
				$event->veto("That alias already exists");
			}
			else {
				$database->Execute("INSERT INTO aliases(oldtag, newtag) VALUES(?, ?)", $pair);
			}
		}
		
		if(is_a($event, 'UserBlockBuildingEvent')) {
			if($event->user->is_admin()) {
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
