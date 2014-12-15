<?php

class RegenThumbTheme extends Themelet {
	/**
	 * Show a form which offers to regenerate the thumb of an image with ID #$image_id
	 *
	 * @param int|string $image_id
	 * @return string
	 */
	public function get_buttons_html($image_id) {
		return "
			".make_form(make_link("regen_thumb"))."
			<input type='hidden' name='image_id' value='$image_id'>
			<input type='submit' value='Regenerate Thumbnail'>
			</form>
		";
	}

	/**
	 * Show a link to the new thumbnail.
	 *
	 * @param Page $page
	 * @param Image $image
	 */
	public function display_results(Page $page, Image $image) {
		$page->set_title("Thumbnail Regenerated");
		$page->set_heading("Thumbnail Regenerated");
		$page->add_html_header("<meta http-equiv=\"cache-control\" content=\"no-cache\">");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Thumbnail", $this->build_thumb_html($image)));
	}
}

