<?php
/*
 * Name: Link to Image
 * Author: Artanis <artanis.00@gmail.com>
 * Description: Show various forms of link to each image, for copy & paste
 */
class LinkImage extends Extension {
	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $page;
		$this->theme->links_block($page, $this->data($event->image));
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Link to Image");
		$sb->add_text_option("ext_link-img_text-link_format", "Text Link Format: ");
		$event->panel->add_block($sb);
	}

	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_string("ext_link-img_text-link_format", '$title - $id ($ext $size $filesize)');
	}

	private function data(Image $image) {
		global $config;

		$text_link = $image->parse_link_template($config->get_string("ext_link-img_text-link_format"));
		$text_link = trim($text_link) == "" ? null : $text_link; // null blank setting so the url gets filled in on the text links.

		return array(
			'thumb_src' => make_http($image->get_thumb_link()),
			'image_src' => make_http($image->get_image_link()),
			'post_link' => make_http(make_link("post/view/{$image->id}")),
			'text_link' => $text_link);
	}
}

