<?php

class FeaturedTheme extends Themelet {
	/*
	 * Show $text on the $page
	 */
	public function display_featured(Page $page, Image $image) {
		$page->add_block(new Block("Featured Image", $this->build_thumb_html($image), "left", 3));
	}

	public function get_buttons_html($image_id) {
		global $user;
		return "
			".make_form(make_link("featured_image/set"))."
			".$user->get_auth_html()."
			<input type='hidden' name='image_id' value='$image_id'>
			<input type='submit' value='Feature This'>
			</form>
		";
	}
}
?>
