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
		if($this->theme == null) $this->theme = get_theme_object("zoom", "ZoomTheme");

		if($event instanceof DisplayingImageEvent) {
			global $config;
			$this->theme->display_zoomer($event->page, $event->image, $config->get_bool("image_zoom", false));
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
