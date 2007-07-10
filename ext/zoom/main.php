<?php

class Zoom extends Extension {
	var $theme;

	public function receive_event($event) {
		if($this->theme == null) $this->theme = get_theme_object("zoom", "ZoomTheme");

		if(is_a($event, 'DisplayingImageEvent')) {
			global $config;
			$this->theme->display_zoomer($event->page, $config->get_bool("image_zoom", false));
		}
		
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Image Zoom");
			$sb->add_bool_option("image_zoom", "Zoom by default: ");
			$event->panel->add_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_bool_from_post("image_zoom");
		}
	}

}
add_event_listener(new Zoom());
?>
