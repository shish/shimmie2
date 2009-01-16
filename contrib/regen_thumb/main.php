<?php
/**
 * Name: Regen Thumb
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Regenerate a thumbnail image
 * Documentation:
 *  This adds a button in the image control section on an
 *  image's view page, which allows an admin to regenerate
 *  an image's thumbnail; useful for instance if the first
 *  attempt failed due to lack of memory, and memory has
 *  since been increased.
 */

class RegenThumb implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("regen_thumb")) {
			global $user;
			if($user->is_admin() && isset($_POST['image_id'])) {
				global $config;
				global $database;
				$image = Image::by_id($config, $database, int_escape($_POST['image_id']));
				send_event(new ThumbnailGenerationEvent($image->hash, $image->ext));
				$this->theme->display_results($event->page, $image);
			}
		}

		if($event instanceof ImageAdminBlockBuildingEvent) {
			if($event->user->is_admin()) {
				$event->add_part($this->theme->get_buttons_html($event->image->id));
			}
		}
	}
}
add_event_listener(new RegenThumb());
?>
