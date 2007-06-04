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
// event handler {{{
	public function receive_event($event) {
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
				$page->set_title("Alias List");
				$page->set_heading("Alias List");
				$page->add_side_block(new NavBlock());
				$page->add_main_block(new Block("Aliases", $this->build_aliases()));
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
// admin page HTML {{{
	private function build_aliases() {
		global $database;
		
		global $user;
		if($user->is_admin()) {
			$action = "<td>Action</td>";
			$add = "
				<tr>
					<form action='".make_link("alias/add")."' method='POST'>
						<td><input type='text' name='oldtag'></td>
						<td><input type='text' name='newtag'></td>
						<td><input type='submit' value='Add'></td>
					</form>
				</tr>
			";
		}
		else {
			$action = "";
			$add = "";
		}
		
		$h_aliases = "";
		$aliases = $database->db->GetAssoc("SELECT oldtag, newtag FROM aliases");
		foreach($aliases as $old => $new) {
			$h_old = html_escape($old);
			$h_new = html_escape($new);
			$h_aliases .= "<tr><td>$h_old</td><td>$h_new</td>";
			if($user->is_admin()) {
				$h_aliases .= "
					<td>
						<form action='".make_link("alias/remove")."' method='POST'>
							<input type='hidden' name='oldtag' value='$h_old'>
							<input type='submit' value='Remove'>
						</form>
					</td>
				";
			}
			$h_aliases .= "</tr>";
		}
		$html = "
			<table border='1'>
				<thead><td>From</td><td>To</td>$action</thead>
				$h_aliases
				$add
			</table>
			<p><a href='".make_link("alias/export")."'>Export</a></p>
		";
		return $html;
	}
	private function get_alias_csv() {
		global $database;
		$csv = "";
		$aliases = $database->db->GetAssoc("SELECT oldtag, newtag FROM aliases");
		foreach($aliases as $old => $new) {
			$csv .= "$old,$new\n";
		}
		return $csv;
	}
// }}}
}
add_event_listener(new AliasEditor());
?>
