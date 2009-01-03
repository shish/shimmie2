<?php
/*
 * SourceSetEvent:
 *   $image_id
 *   $source
 *
 */
class SourceSetEvent extends Event {
	var $image_id;
	var $source;

	public function SourceSetEvent($image_id, $source) {
		$this->image_id = $image_id;
		$this->source = $source;
	}
}


/*
 * TagSetEvent:
 *   $image_id
 *   $tags
 *
 */
class TagSetEvent extends Event {
	var $image_id;
	var $tags;

	public function TagSetEvent($image_id, $tags) {
		$this->image_id = $image_id;
		$this->tags = tag_explode($tags);
	}
}

class TagEdit implements Extension {
	var $theme;
// event handling {{{
	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("tag_edit")) {
			global $page;
			if($event->get_arg(0) == "replace") {
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

		if($event instanceof ImageInfoSetEvent) {
			if($this->can_tag()) {
				global $database;
				send_event(new TagSetEvent($event->image_id, $_POST['tag_edit__tags']));
				if($this->can_source()) {
					send_event(new SourceSetEvent($event->image_id, $_POST['tag_edit__source']));
				}
			}
			else {
				$this->theme->display_error($event->page, "Error", "Anonymous tag editing is disabled");
			}
		}
		
		if($event instanceof TagSetEvent) {
			global $database;
			$database->set_tags($event->image_id, $event->tags);
		}

		if($event instanceof SourceSetEvent) {
			global $database;
			$database->set_source($event->image_id, $event->source);
		}

		if($event instanceof ImageDeletionEvent) {
			global $database;
			$database->delete_tags_from_image($event->image->id);
		}

		if($event instanceof AdminBuildingEvent) {
			$this->theme->display_mass_editor($event->page);
		}

		// When an alias is added, oldtag becomes inaccessable
		if($event instanceof AddAliasEvent) {
			$this->mass_tag_edit($event->oldtag, $event->newtag);
		}

		if($event instanceof ImageInfoBoxBuildingEvent) {
			global $user;
			global $config;
			if($config->get_bool("tag_edit_anon") || !$user->is_anonymous()) {
				$event->add_part($this->theme->get_tag_editor_html($event->image), 40);
			}
			if($config->get_bool("source_edit_anon") || !$user->is_anonymous()) {
				$event->add_part($this->theme->get_source_editor_html($event->image), 41);
			}
		}

		if($event instanceof SetupBuildingEvent) {
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
				WHERE id=? OR id=?
				", array($search_id, $replace_id));
		}
		else if($search_id) {
			$database->Execute("UPDATE tags SET tag=? WHERE tag=?", Array($replace, $search));
		}
	}
// }}}
}
add_event_listener(new TagEdit());
?>
