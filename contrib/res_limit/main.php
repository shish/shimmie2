<?php
/*
 * Name: Resolution Limiter
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Allows the admin to set min / max image dimentions
 */
class ResolutionLimit extends Extension {
	public function get_priority() {return 40;} // early, to veto ImageUploadEvent

	public function onImageAddition(ImageAdditionEvent $event) {
		global $config;
		$min_w = $config->get_int("upload_min_width", -1);
		$min_h = $config->get_int("upload_min_height", -1);
		$max_w = $config->get_int("upload_max_width", -1);
		$max_h = $config->get_int("upload_max_height", -1);
		$ratios = explode(" ", $config->get_string("upload_ratios", ""));

		$image = $event->image;

		if($min_w > 0 && $image->width < $min_w) throw new UploadException("Image too small");
		if($min_h > 0 && $image->height < $min_h) throw new UploadException("Image too small");
		if($max_w > 0 && $image->width > $max_w) throw new UploadException("Image too large");
		if($max_h > 0 && $image->height > $max_h) throw new UploadException("Image too large");

		if(count($ratios) > 0) {
			$ok = false;
			$valids = 0;
			foreach($ratios as $ratio) {
				$parts = explode(":", $ratio);
				if(count($parts) < 2) continue;
				$valids++;
				$width = $parts[0];
				$height = $parts[1];
				if($image->width / $width == $image->height / $height) {
					$ok = true;
					break;
				}
			}
			if($valids > 0 && !$ok) {
				throw new UploadException(
					"Image needs to be in one of these ratios: ".
					html_escape($config->get_string("upload_ratios", "")));
			}
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
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
?>
