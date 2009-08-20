<?php
/*
 * Name: Link to Image
 * Author: Artanis <artanis.00@gmail.com>
 * Description: Show various forms of link to each image, for copy & paste
 */
class LinkImage implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

			if(($event instanceof DisplayingImageEvent)) {
				$this->theme->links_block($page, $this->data($event->image));
			}
			if($event instanceof SetupBuildingEvent) {
				$sb = new SetupBlock("Link to Image");
				$sb->add_text_option("ext_link-img_text-link_format", "Text Link Format: ");
				$event->panel->add_block($sb);
			}
			if($event instanceof InitExtEvent) {
				//just set default if empty.
				$config->set_default_string("ext_link-img_text-link_format",
										'$title - $id ($ext $size $filesize)');
			}
		}

	private function hostify($str) {
		$str = str_replace(" ", "%20", $str);
		if(strpos($str, "ttp://") > 0) {
			return $str;
		}
		else {
			return "http://" . $_SERVER["HTTP_HOST"] . $str;
		}
	}
	private function data($image) {
		global $config;

		$text_link = $image->parse_link_template($config->get_string("ext_link-img_text-link_format"));
		$text_link = trim($text_link) == "" ? null : $text_link; // null blank setting so the url gets filled in on the text links.

		return array(
			'thumb_src'	=> $this->hostify($image->get_thumb_link()),
			'image_src'	=> $this->hostify($image->get_image_link()),
			'post_link'	=> $this->hostify($_SERVER["REQUEST_URI"]),
			'text_link' => $text_link);
	}
}
add_event_listener(new LinkImage());
?>
