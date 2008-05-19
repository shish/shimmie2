<?php

class FeaturedTheme extends Themelet {
	/*
	 * Show $text on the $page
	 */
	public function display_featured($page, $image) {
		$page->add_block(new Block("Featured Image", $this->build_thumb_html($image), "left", 3));
	}

	public function display_buttons($page, $image_id) {
		$html = "
			<form action='".make_link("set_feature")."' method='POST'>
			<input type='hidden' name='image_id' value='$image_id'>
			<input type='submit' value='Featue This'>
			</form>
		";
		$page->add_block(new Block("Featured Image", $html, "left"));
	}
}
?>
