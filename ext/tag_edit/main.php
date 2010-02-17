<?php
/*
 * Name: Tag Editor
 * Author: Shish
 * Description: Allow images to have tags assigned to them
 */

/*
 * SourceSetEvent:
 *   $image_id
 *   $source
 *
 */
class SourceSetEvent extends Event {
	var $image;
	var $source;

	public function SourceSetEvent(Image $image, $source) {
		$this->image = $image;
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
	var $image;
	var $tags;

	public function TagSetEvent(Image $image, $tags) {
		$this->image = $image;
		$this->tags = Tag::explode($tags);
	}
}

/*
 * LockSetEvent:
 *   $image_id
 *   $locked
 *
 */
class LockSetEvent extends Event {
	var $image;
	var $locked;

	public function LockSetEvent(Image $image, $locked) {
		assert(is_bool($locked));
		$this->image = $image;
		$this->locked = $locked;
	}
}

class TagEdit implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("tag_edit")) {
			if($event->get_arg(0) == "replace") {
				if($user->is_admin() && isset($_POST['search']) && isset($_POST['replace'])) {
					$search = $_POST['search'];
					$replace = $_POST['replace'];
					$this->mass_tag_edit($search, $replace);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("admin"));
				}
			}
		}

		if($event instanceof ImageInfoSetEvent) {
			if($this->can_tag($event->image)) {
				send_event(new TagSetEvent($event->image, $_POST['tag_edit__tags']));
				if($this->can_source($event->image)) {
					send_event(new SourceSetEvent($event->image, $_POST['tag_edit__source']));
				}
			}
			else {
				$this->theme->display_error($page, "Error", "Anonymous tag editing is disabled");
			}
			if($user->is_admin()) {
				send_event(new LockSetEvent($event->image, $_POST['tag_edit__locked']=="on"));
			}
		}

		if($event instanceof TagSetEvent) {
			if($user->is_admin() || !$event->image->is_locked()) {
				$event->image->set_tags($event->tags);
			}
		}

		if($event instanceof SourceSetEvent) {
			if($user->is_admin() || !$event->image->is_locked()) {
				$event->image->set_source($event->source);
			}
		}

		if($event instanceof LockSetEvent) {
			if($user->is_admin()) {
				log_debug("tag_edit", "Setting Image #{$event->image->id} lock to: {$event->locked}");
				$event->image->set_locked($event->locked);
			}
		}

		if($event instanceof ImageDeletionEvent) {
			$event->image->delete_tags_from_image();
		}

		if($event instanceof AdminBuildingEvent) {
			$this->theme->display_mass_editor($page);
		}

		// When an alias is added, oldtag becomes inaccessable
		if($event instanceof AddAliasEvent) {
			$this->mass_tag_edit($event->oldtag, $event->newtag);
		}

		if($event instanceof ImageInfoBoxBuildingEvent) {
			if($this->can_tag($event->image)) {
				$event->add_part($this->theme->get_tag_editor_html($event->image), 40);
			}
			if($this->can_source($event->image)) {
				$event->add_part($this->theme->get_source_editor_html($event->image), 41);
			}
			if($user->is_admin()) {
				$event->add_part($this->theme->get_lock_editor_html($event->image), 42);
			}
		}

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Tag Editing");
			$sb->add_bool_option("tag_edit_anon", "Allow anonymous tag editing: ");
			$sb->add_bool_option("source_edit_anon", "<br>Allow anonymous source editing: ");
			$event->panel->add_block($sb);
		}
	}


	private function can_tag($image) {
		global $config, $user;
		return (
			($config->get_bool("tag_edit_anon") || !$user->is_anonymous()) &&
			($user->is_admin() || !$image->is_locked())
			);
	}

	private function can_source($image) {
		global $config, $user;
		return (
			($config->get_bool("source_edit_anon") || !$user->is_anonymous()) &&
			($user->is_admin() || !$image->is_locked())
			);
	}

	private function mass_tag_edit($search, $replace) {
		global $database;
		global $config;

		$search_set = Tag::explode($search);
		$replace_set = Tag::explode($replace);

		$last_id = -1;
		while(true) {
			// make sure we don't look at the same images twice.
			// search returns high-ids first, so we want to look
			// at images with lower IDs than the previous.
			$search_forward = $search_set;
			if($last_id >= 0) $search_forward[] = "id<$last_id";

			$images = Image::find_images(0, 100, $search_forward);
			if(count($images) == 0) break;

			foreach($images as $image) {
				// remove the search'ed tags
				$before = $image->get_tag_array();
				$after = array();
				foreach($before as $tag) {
					if(!in_array($tag, $search_set)) {
						$after[] = $tag;
					}
				}

				// add the replace'd tags
				foreach($replace_set as $tag) {
					$after[] = $tag;
				}

				$image->set_tags($after);

				$last_id = $image->id;
			}
		}
	}
}
add_event_listener(new TagEdit());
?>
