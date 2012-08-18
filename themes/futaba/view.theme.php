<?php

class CustomViewImageTheme extends ViewImageTheme {
	public function display_page(Image $image, $editor_parts) {
		global $page;
		$page->set_title("Image {$image->id}: ".html_escape($image->get_tag_list()));
		$page->set_heading(html_escape($image->get_tag_list()));
		$page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 10));
	}
}
?>
