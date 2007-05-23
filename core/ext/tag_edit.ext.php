<?php

class TagEdit extends Extension {
// event handling {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "tag_edit")) {
			global $page;
			if($event->get_arg(0) == "set") {
				if($this->can_tag()) {
					global $database;
					$i_image_id = int_escape($_POST['image_id']);
					$query = $_POST['query'];
					$database->set_tags($i_image_id, $_POST['tags']);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/view/$i_image_id", $query));
				}
				else {
					$page->set_title("Tag Edit Denied");
					$page->set_heading("Tag Edit Denied");
					$page->add_side_block(new NavBlock());
					$page->add_main_block(new Block("Error", "Anonymous tag editing is disabled"));
				}
			}
			else if($event->get_arg(0) == "replace") {
				global $user;
				if($user->is_admin() && isset($_POST['search']) && isset($_POST['replace'])) {
					global $page;
					$this->mass_tag_edit($_POST['search'], $_POST['replace']);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("admin"));
				}
			}
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			global $page;
			$page->add_main_block(new Block(null, $this->build_tag_editor($event->image)), 5);
		}

		if(is_a($event, 'TagSetEvent')) {
			global $database;
			$database->set_tags($event->image_id, $event->tags);
		}

		if(is_a($event, 'ImageDeletionEvent')) {
			global $database;
			$database->delete_tags_from_image($event->image->id);
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			global $page;
			$page->add_main_block(new Block("Mass Tag Edit", $this->build_mass_tag_edit()));
		}

		// When an alias is added, oldtag becomes inaccessable
		if(is_a($event, 'AddAliasEvent')) {
			$this->mass_tag_edit($event->oldtag, $event->newtag);
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Tag Editing");
			$sb->add_bool_option("tag_edit_anon", "Allow anonymous editing: ");
			$event->panel->add_main_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_bool_from_post("tag_edit_anon");
		}
	}
// }}}
// do things {{{
	private function can_tag() {
		global $config, $user;
		return $config->get_bool("tag_edit_anon") || !$user->is_anonymous();
	}
// }}}
// edit {{{
	private function mass_tag_edit($search, $replace) {
		// FIXME: deal with collisions
		global $database;
		$database->db->Execute("UPDATE tags SET tag=? WHERE tag=?", Array($replace, $search));
	}
// }}}
// HTML {{{
	private function build_tag_editor($image) {
		global $database;
		
		if(isset($_GET['search'])) {
			$h_query = "search=".url_escape($_GET['search']);
		}
		else {
			$h_query = "";
		}

		$h_tags = html_escape($image->get_tag_list());
		$i_image_id = int_escape($image->id);

		return "
		<p><form action='".make_link("tag_edit/set")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='query' value='$h_query'>
			<input type='text' size='50' name='tags' value='$h_tags'>
			<input type='submit' value='Set'>
		</form>
		";
	}
	private function build_mass_tag_edit() {
		return "
		<form action='".make_link("tag_edit/replace")."' method='POST'>
			<table border='1' style='width: 200px;'>
				<tr><td>Search</td><td><input type='text' name='search'></tr>
				<tr><td>Replace</td><td><input type='text' name='replace'></td></tr>
				<tr><td colspan='2'><input type='submit' value='Replace'></td></tr>
			</table>
		</form>
		";
	}
// }}}
}
add_event_listener(new TagEdit());
?>
