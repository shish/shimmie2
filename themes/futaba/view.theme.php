<?php

class CustomViewImageTheme extends ViewImageTheme {
	public function display_page($page, $image, $editor_parts) {
		$page->set_title("Image {$image->id}: ".html_escape($image->get_tag_list()));
		$page->set_heading(html_escape($image->get_tag_list()));
		$page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 10));
	}
}
?>
