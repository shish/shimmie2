<?php

class ResizeImageTheme extends Themelet {
	/*
	 * Display a link to resize an image
	 */
	public function get_resize_html(/*int*/ $image_id) {
		global $user, $config;

		$i_image_id = int_escape($image_id);
		$default_width = $config->get_int('resize_default_width');
		$default_height = $config->get_int('resize_default_height');
		
		$html .= "
			".make_form(make_link('resize/'.$i_image_id), 'POST')."
				<input type='hidden' name='image_id' value='$i_image_id'>
				<input id='resize_width'  style='width: auto;' size='5' name='resize_width' type='text' value='".$default_width."'> x
				<input id='resize_height' style='width: auto;' size='5' name='resize_height' type='text' value='".$default_height."'>
				<input id='resizebutton' type='submit' value='Resize'>
			</form>
		";
		
		return $html;
	}
	
	public function display_resize_error(Page $page, /*string*/ $title, /*string*/ $message) {
		$page->set_title("Resize Image");
		$page->set_heading("Resize Image");
		$page->add_block(new NavBlock());
		$page->add_block(new Block($title, $message));
	}
}
?>
