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
			if($user->is_admin()) {
				if($event->get_arg(0) == "add") {
					if(isset($_POST['oldtag']) && isset($_POST['newtag'])) {
						send_event(new AddAliasEvent($_POST['oldtag'], $_POST['newtag']));
					}
				}
				else if($event->get_arg(0) == "remove") {
					if(isset($_POST['oldtag'])) {
						global $database;
						$database->db->Execute("DELETE FROM aliases WHERE oldtag=?", array($_POST['oldtag']));

						global $page;
						$page->set_mode("redirect");
						$page->set_redirect(make_link("admin"));
					}
				}
			}
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			global $page;
			$page->add_main_block(new Block("Edit Aliases", $this->build_aliases()));
		}

		if(is_a($event, 'AddAliasEvent')) {
			global $database;
			$database->db->Execute("INSERT INTO aliases(oldtag, newtag) VALUES(?, ?)", array($event->oldtag, $event->newtag));

			global $page;
			$page->set_mode("redirect");
			$page->set_redirect(make_link("admin"));
		}
	}
// }}}
// admin page HTML {{{
	private function build_aliases() {
		global $database;
		$h_aliases = "";
		$aliases = $database->db->GetAssoc("SELECT oldtag, newtag FROM aliases");
		foreach($aliases as $old => $new) {
			$h_old = html_escape($old);
			$h_new = html_escape($new);
			$h_aliases .= "
				<tr>
					<td>$h_old</td>
					<td>$h_new</td>
					<td>
						<form action='".make_link("alias/remove")."' method='POST'>
							<input type='hidden' name='oldtag' value='$h_old'>
							<input type='submit' value='Remove'>
						</form>
					</td>
				</tr>
			";
		}
		$html = "
			<table border='1'>
				<thead><td>From</td><td>To</td><td>Action</td></thead>
				$h_aliases
				<tr>
					<form action='".make_link("alias/add")."' method='POST'>
						<td><input type='text' name='oldtag'></td>
						<td><input type='text' name='newtag'></td>
						<td><input type='submit' value='Add'></td>
					</form>
				</tr>
			</table>
		";
		return $html;
	}
// }}}
}
add_event_listener(new AliasEditor());
?>
