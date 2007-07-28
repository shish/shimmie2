<?php

class RegenThumbTheme extends Themelet {
	/*
	 * Show a form which offers to regenerate the thumb of an image with ID #$image_id
	 */
	public function display_buttons($page, $image_id) {
		$html = "
			<form action='".make_link("regen_thumb")."' method='POST'>
			<input type='hidden' name='image_id' value='$image_id'>
			<select name='program'>
				<option value='convert'>ImageMagick</option>
				<option value='gd'>GD</option>
				<!-- <option value='epeg'>EPEG (for JPEG only)</option> -->
			</select>
			<input type='submit' value='Regenerate'>
			</form>
		";
		$page->add_block(new Block("Regen Thumb", $html, "left"));
	}

	/*
	 * Show a link to the new thumbnail
	 */
	public function display_results($page, $image) {
		$page->set_title("Thumbnail Regenerated");
		$page->set_heading("Thumbnail Regenerated");
		$page->add_header("<meta http-equiv=\"cache-control\" content=\"no-cache\">");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Thumbnail", build_thumb_html($image)));
	}
}
?>
