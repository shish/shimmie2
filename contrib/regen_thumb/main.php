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

class RegenThumb extends SimpleExtension {
	public function onPageRequest($event) {
		global $config, $database, $page, $user;

		if($event->page_matches("regen_thumb") && $user->is_admin() && isset($_POST['image_id'])) {
			$image = Image::by_id(int_escape($_POST['image_id']));
			send_event(new ThumbnailGenerationEvent($image->hash, $image->ext, true));
			$this->theme->display_results($page, $image);
		}
	}

	public function onImageAdminBlockBuilding($event) {
		global $user;
		if($user->is_admin()) {
			$event->add_part($this->theme->get_buttons_html($event->image->id));
		}
	}
}
?>
