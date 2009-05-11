<?php
/**
 * Name: Image Zoom
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Scales down too-large images using browser based scaling
 */

class Zoom implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if($this->theme == null) $this->theme = get_theme_object($this);

		if($event instanceof DisplayingImageEvent) {
			$this->theme->display_zoomer($page, $event->image, $config->get_bool("image_zoom", false));
		}

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Image Zoom");
			$sb->add_bool_option("image_zoom", "Zoom by default: ");
			$event->panel->add_block($sb);
		}
	}

}
add_event_listener(new Zoom());
?>
