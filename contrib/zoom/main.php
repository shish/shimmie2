<?php
/*
 * Name: Image Zoom
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Scales down too-large images using browser based scaling
 */

class Zoom extends SimpleExtension {
	public function onDisplayingImage($event) {
		global $config, $page;
		$this->theme->display_zoomer($page, $event->image, $config->get_bool("image_zoom", false));
	}

	public function onSetupBuilding($event) {
		$sb = new SetupBlock("Image Zoom");
		$sb->add_bool_option("image_zoom", "Zoom by default: ");
		$event->panel->add_block($sb);
	}
}
?>
