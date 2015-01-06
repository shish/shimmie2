<?php

class RotateImageTheme extends Themelet {
	/**
	 * Display a link to rotate an image.
	 *
	 * @param int $image_id
	 * @return string
	 */
	public function get_rotate_html(/*int*/ $image_id) {
		$html = "
			".make_form(make_link('rotate/'.$image_id), 'POST')."
				<input type='hidden' name='image_id' value='$image_id'>
				<input id='rotate_deg' name='rotate_deg' type='number' placeholder='Rotation degrees'>
				<input id='rotatebutton' type='submit' value='Rotate'>
			</form>
		";
		
		return $html;
	}

	/**
	 * Display the error.
	 *
	 * @param Page $page
	 * @param string $title
	 * @param string $message
	 */
	public function display_rotate_error(Page $page, /*string*/ $title, /*string*/ $message) {
		$page->set_title("Rotate Image");
		$page->set_heading("Rotate Image");
		$page->add_block(new NavBlock());
		$page->add_block(new Block($title, $message));
	}
}

