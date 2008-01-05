<?php

class TagEdit extends Extension {
	var $theme;
// event handling {{{
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("tag_edit", "TagEditTheme");

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "tag_edit")) {
			global $page;
			if($event->get_arg(0) == "set") {
				if($this->can_tag()) {
					global $database;
					$i_image_id = int_escape($_POST['image_id']);
					$query = $_POST['query'];
					send_event(new TagSetEvent($i_image_id, $_POST['tags']));
					if($this->can_source()) {
						send_event(new SourceSetEvent($i_image_id, $_POST['source']));
					}
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/view/$i_image_id", $query));
				}
				else {
					$this->theme->display_error($event->page, "Error", "Anonymous tag editing is disabled");
				}
			}
			else if($event->get_arg(0) == "replace") {
				global $user;
				if($user->is_admin() && isset($_POST['search']) && isset($_POST['replace'])) {
					$search = $_POST['search'];
					$replace = $_POST['replace'];
					global $page;
					if(strpos($search, " ") === false && strpos($replace, " ") === false) {
						$this->mass_tag_edit($search, $replace);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("admin"));
					}
					else {
						$this->theme->display_error($page, "Search &amp; Replace Error",
							"Bulk replace can only do single tags -- don't use spaces!");
					}
				}
			}
		}

		if(is_a($event, 'TagSetEvent')) {
			global $database;
			$database->set_tags($event->image_id, $event->tags);
		}

		if(is_a($event, 'SourceSetEvent')) {
			global $database;
			$database->set_source($event->image_id, $event->source);
		}

		if(is_a($event, 'ImageDeletionEvent')) {
			global $database;
			$database->delete_tags_from_image($event->image->id);
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			$this->theme->display_mass_editor($event->page);
		}

		// When an alias is added, oldtag becomes inaccessable
		if(is_a($event, 'AddAliasEvent')) {
			$this->mass_tag_edit($event->oldtag, $event->newtag);
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Tag Editing");
			$sb->add_bool_option("tag_edit_anon", "Allow anonymous tag editing: ");
			$sb->add_bool_option("source_edit_anon", "<br>Allow anonymous source editing: ");
			$event->panel->add_block($sb);
		}
	}
// }}}
// do things {{{
	private function can_tag() {
		global $config, $user;
		return $config->get_bool("tag_edit_anon") || !$user->is_anonymous();
	}

	private function can_source() {
		global $config, $user;
		return $config->get_bool("source_edit_anon") || !$user->is_anonymous();
	}

	private function mass_tag_edit($search, $replace) {
		global $database;
		$search_id = $database->db->GetOne("SELECT id FROM tags WHERE tag=?", array($search));
		$replace_id = $database->db->GetOne("SELECT id FROM tags WHERE tag=?", array($replace));
		if($search_id && $replace_id) {
			// FIXME: what if the (image_id,tag_id) pair already exists?
			$database->Execute("UPDATE IGNORE image_tags SET tag_id=? WHERE tag_id=?", Array($replace_id, $search_id));
			$database->Execute("DELETE FROM image_tags WHERE tag_id=?", Array($search_id));
			$database->Execute("
				UPDATE tags
				SET count=(SELECT COUNT(image_id) FROM image_tags WHERE tag_id=tags.id GROUP BY tag_id)
				WHERE id=?
				", array($replace_id));
		}
		else if($search_id) {
			$database->Execute("UPDATE tags SET tag=? WHERE tag=?", Array($replace, $search));
		}
	}
// }}}
}
add_event_listener(new TagEdit());
?>
