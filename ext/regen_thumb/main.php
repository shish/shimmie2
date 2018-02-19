<?php
/*
 * Name: Regen Thumb
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Regenerate a thumbnail image
 * Documentation:
 *  This adds a button in the image control section on an
 *  image's view page, which allows an admin to regenerate
 *  an image's thumbnail; useful for instance if the first
 *  attempt failed due to lack of memory, and memory has
 *  since been increased.
 */

class RegenThumb extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $database, $page, $user;

		if($event->page_matches("regen_thumb/one") && $user->can("delete_image") && isset($_POST['image_id'])) {
			$image = Image::by_id(int_escape($_POST['image_id']));
			send_event(new ThumbnailGenerationEvent($image->hash, $image->ext, true));
			$this->theme->display_results($page, $image);
		}
		if($event->page_matches("regen_thumb/mass") && $user->can("delete_image") && isset($_POST['tags'])) {
			$tags = Tag::explode(strtolower($_POST['tags']), false);
			$images = Image::find_images(0, 10000, $tags);

			foreach($images as $image) {
				send_event(new ThumbnailGenerationEvent($image->hash, $image->ext, true));
			}

			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/list"));
		}
	}

	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		global $user;
		if($user->can("delete_image")) {
			$event->add_part($this->theme->get_buttons_html($event->image->id));
		}
	}

	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $user;
		if($user->can("delete_image") && !empty($event->search_terms)) {
			$event->add_control($this->theme->mtr_html(implode(" ", $event->search_terms)));
		}
	}
}

