<?php
/**
 * Name: Resolution Limiter
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Allows the admin to set min / max image dimentions
 */
class ResolutionLimit extends Extension {
	public function receive_event($event) {
		if($event instanceof ImageAdditionEvent) {
			global $config;
			$min_w = $config->get_int("upload_min_width", -1);
			$min_h = $config->get_int("upload_min_height", -1);
			$max_w = $config->get_int("upload_max_width", -1);
			$max_h = $config->get_int("upload_max_height", -1);
			$ratios = explode(" ", $config->get_string("upload_ratios", ""));
			$ratios = array_filter($ratios, "strlen");
			
			$image = $event->image;

			if($min_w > 0 && $image->width < $min_w) $event->veto("Image too small");
			if($min_h > 0 && $image->height < $min_h) $event->veto("Image too small");
			if($max_w > 0 && $image->width > $min_w) $event->veto("Image too large");
			if($max_h > 0 && $image->height > $min_h) $event->veto("Image too large");

			if(count($ratios) > 0) {
				$ok = false;
				foreach($ratios as $ratio) {
					$parts = explode(":", $ratio);
					if(count($parts) < 2) continue;
					$width = $parts[0];
					$height = $parts[1];
					if($image->width / $width == $image->height / $height) {
						$ok = true;
						break;
					}
				}
				if(!$ok) {
					$event->veto("Image needs to be in one of these ratios: ".html_escape($config->get_string("upload_ratios", "")));
				}
			}
		}
		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Resolution Limits");

			$sb->add_label("Min ");
			$sb->add_int_option("upload_min_width");
			$sb->add_label(" x ");
			$sb->add_int_option("upload_min_height");
			$sb->add_label(" px");

			$sb->add_label("<br>Max ");
			$sb->add_int_option("upload_max_width");
			$sb->add_label(" x ");
			$sb->add_int_option("upload_max_height");
			$sb->add_label(" px");
			
			$sb->add_label("<br>(-1 for no limit)");

			$sb->add_label("<br>Ratios ");
			$sb->add_text_option("upload_ratios");
			$sb->add_label("<br>(eg. '4:3 16:9', blank for no limit)");
			
			$event->panel->add_block($sb);
		}
	}
}
add_event_listener(new ResolutionLimit(), 40); // early, to veto UIE
?>
