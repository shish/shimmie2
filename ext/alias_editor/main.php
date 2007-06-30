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
// event handler {{{
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("alias_editor", "AliasEditorTheme");

		if(is_a($event, 'PageRequestEvent') && ($event->page == "alias")) {
			global $user;
			if($event->get_arg(0) == "add") {
				if($user->is_admin()) {
					if(isset($_POST['oldtag']) && isset($_POST['newtag'])) {
						send_event(new AddAliasEvent($_POST['oldtag'], $_POST['newtag']));
					}
				}
			}
			else if($event->get_arg(0) == "remove") {
				if($user->is_admin()) {
					if(isset($_POST['oldtag'])) {
						global $database;
						$database->Execute("DELETE FROM aliases WHERE oldtag=?", array($_POST['oldtag']));

						global $page;
						$page->set_mode("redirect");
						$page->set_redirect(make_link("admin"));
					}
				}
			}
			else if($event->get_arg(0) == "list") {
				global $page;
				global $database;
				$this->theme->display_aliases($page, $database->db->GetAssoc("SELECT oldtag, newtag FROM aliases"), $user->is_admin());
			}
			else if($event->get_arg(0) == "export") {
				global $page;
				$page->set_mode("data");
				$page->set_type("text/plain");
				$page->set_data($this->get_alias_csv());
			}
		}

		if(is_a($event, 'AddAliasEvent')) {
			global $database;
			$database->Execute("INSERT INTO aliases(oldtag, newtag) VALUES(?, ?)", array($event->oldtag, $event->newtag));

			global $page;
			$page->set_mode("redirect");
			$page->set_redirect(make_link("admin"));
		}
		
		if(is_a($event, 'UserBlockBuildingEvent')) {
			if($event->user->is_admin()) {
				$event->add_link("Alias Editor", make_link("alias/list"));
			}
		}
	}
// }}}
}
add_event_listener(new AliasEditor());
?>
