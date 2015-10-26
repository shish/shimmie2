<?php

class ResizeImageTheme extends Themelet {
	/*
	 * Display a link to resize an image
	 */
	public function get_resize_html(Image $image) {
		global $config;

		$default_width = $config->get_int('resize_default_width');
		$default_height = $config->get_int('resize_default_height');

		if(!$default_width) $default_width = $image->width;
		if(!$default_height) $default_height = $image->height;
		
		$html = "
			".make_form(make_link("resize/{$image->id}"), 'POST')."
				<input type='hidden' name='image_id' value='{$image->id}'>
				<input id='original_width'  name='original_width'  type='hidden' value='{$image->width}'>
				<input id='original_height' name='original_height' type='hidden' value='{$image->height}'>
				<input id='resize_width'  style='width: 70px;' name='resize_width'  type='number' min='1' value='".$default_width."'> x
				<input id='resize_height' style='width: 70px;' name='resize_height' type='number' min='1' value='".$default_height."'>
				<br><label><input type='checkbox' id='resize_aspect' name='resize_aspect' style='max-width: 20px;' checked='checked'> Keep Aspect</label>
				<br><input id='resizebutton' type='submit' value='Resize'>
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

