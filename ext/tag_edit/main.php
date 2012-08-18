<?php
/*
 * Name: Tag Editor
 * Author: Shish
 * Description: Allow images to have tags assigned to them
 */

/*
 * OwnerSetEvent:
 *   $image_id
 *   $source
 *
 */
class OwnerSetEvent extends Event {
	var $image;
	var $owner;

	public function OwnerSetEvent(Image $image, User $owner) {
		$this->image = $image;
		$this->owner = $owner;
	}
}


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

class TagEdit extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $user, $page;
		if($event->page_matches("tag_edit")) {
			if($event->get_arg(0) == "replace") {
				if($user->can("mass_tag_edit") && isset($_POST['search']) && isset($_POST['replace'])) {
					$search = $_POST['search'];
					$replace = $_POST['replace'];
					$this->mass_tag_edit($search, $replace);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("admin"));
				}
			}
			if($event->get_arg(0) == "mass_source_set") {
				if($user->can("mass_tag_edit") && isset($_POST['tags']) && isset($_POST['source'])) {
					$this->mass_source_edit($_POST['tags'], $_POST['source']);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/list"));
				}
			}
		}
	}

	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $user;
		if($user->can("bulk_edit_image_source") && !empty($event->search_terms)) {
			$event->add_control($this->theme->mss_html(implode(" ", $event->search_terms)));
		}
	}

	public function onImageInfoSet(ImageInfoSetEvent $event) {
		global $user, $page;
		if($user->can("edit_image_owner")) {
			$owner = User::by_name($_POST['tag_edit__owner']);
			send_event(new OwnerSetEvent($event->image, $owner));
		}
		if($this->can_tag($event->image)) {
			send_event(new TagSetEvent($event->image, $_POST['tag_edit__tags']));
		}
		if($this->can_source($event->image)) {
			send_event(new SourceSetEvent($event->image, $_POST['tag_edit__source']));
		}
		if($user->can("edit_image_lock")) {
			$locked = isset($_POST['tag_edit__locked']) && $_POST['tag_edit__locked']=="on";
			send_event(new LockSetEvent($event->image, $locked));
		}
	}

	public function onOwnerSet(OwnerSetEvent $event) {
		global $user;
		if($user->can("edit_image_owner") && (!$event->image->is_locked() || $user->can("edit_image_lock"))) {
			$event->image->set_owner($event->owner);
		}
	}

	public function onTagSet(TagSetEvent $event) {
		global $user;
		if($user->can("edit_image_tag") && (!$event->image->is_locked() || $user->can("edit_image_lock"))) {
			$event->image->set_tags($event->tags);
		}
	}

	public function onSourceSet(SourceSetEvent $event) {
		global $user;
		if($user->can("edit_image_source") && (!$event->image->is_locked() || $user->can("edit_image_lock"))) {
			$event->image->set_source($event->source);
		}
	}

	public function onLockSet(LockSetEvent $event) {
		global $user;
		if($user->can("edit_image_lock")) {
			$event->image->set_locked($event->locked);
		}
	}

	public function onImageDeletion(ImageDeletionEvent $event) {
		$event->image->delete_tags_from_image();
	}

	public function onAdminBuilding(AdminBuildingEvent $event) {
		$this->theme->display_mass_editor();
	}

	// When an alias is added, oldtag becomes inaccessable
	public function onAddAlias(AddAliasEvent $event) {
		$this->mass_tag_edit($event->oldtag, $event->newtag);
	}

	public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event) {
		global $user;
		$event->add_part($this->theme->get_user_editor_html($event->image), 39);
		$event->add_part($this->theme->get_tag_editor_html($event->image), 40);
		$event->add_part($this->theme->get_source_editor_html($event->image), 41);
		$event->add_part($this->theme->get_lock_editor_html($event->image), 42);
	}


	private function can_tag(Image $image) {
		global $config, $user;
		return ($user->can("edit_image_tag") || !$image->is_locked());
	}

	private function can_source(Image $image) {
		global $config, $user;
		return ($user->can("edit_image_source") || !$image->is_locked());
	}

	private function mass_tag_edit($search, $replace) {
		global $database;
		global $config;

		$search_set = Tag::explode($search);
		$replace_set = Tag::explode($replace);

		log_info("tag_edit", "Mass editing tags: '$search' -> '$replace'");

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

	private function mass_source_edit($tags, $source) {
		global $database;
		global $config;

		$tags = Tag::explode($tags);

		$last_id = -1;
		while(true) {
			// make sure we don't look at the same images twice.
			// search returns high-ids first, so we want to look
			// at images with lower IDs than the previous.
			$search_forward = $tags;
			if($last_id >= 0) $search_forward[] = "id<$last_id";

			$images = Image::find_images(0, 100, $search_forward);
			if(count($images) == 0) break;

			foreach($images as $image) {
				$image->set_source($source);
				$last_id = $image->id;
			}
		}
	}
}
?>
