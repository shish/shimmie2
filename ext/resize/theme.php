<?php

class ResizeImageTheme extends Themelet {
	/*
	 * Display a link to resize an image
	 */
	public function get_resize_html(/*int*/ $image_id) {
		global $user, $config;

		$i_image_id = int_escape($image_id);
		
		$html = "
			".make_form(make_link("resize"),'POST',false,'resize_image')."
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='submit' value='Resize' id='resize_image_submit' />
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
	
	public function display_resize_page(Page $page, /*int*/ $image_id) {
		global $config;
		
		$default_width = $config->get_int('resize_default_width');
		$default_height = $config->get_int('resize_default_height');
		
		$image = Image::by_id($image_id);
		$thumbnail = $this->build_thumb_html($image, null);
		
		$html = "<div style='clear:both;'></div>
				<p>Resize Image ID ".$image_id."<br>".$thumbnail."</p>
				<p>Please note: You will have to refresh the image page, or empty your browser cache.</p>
				<p>Enter the new size for the image, or leave blank to scale the image automatically.</p><br>"
				.make_form(make_link('resize/'.$image_id), 'POST', $multipart=True,'form_resize')."
				<input type='hidden' name='image_id' value='$image_id'>
				<table id='large_upload_form'>
					<tr><td>New Width</td><td colspan='3'><input id='resize_width' name='resize_width' type='text' value='".$default_width."'></td></tr>
					<tr><td>New Height</td><td colspan='3'><input id='resize_height' name='resize_height' type='text' value='".$default_height."'></td></tr>
					<tr><td colspan='4'><input id='resizebutton' type='submit' value='Resize'></td></tr>
				</table>
			</form>
		";
	
		$page->set_title("Resize Image");
		$page->set_heading("Resize Image");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Resize Image", $html, "main", 20));
	
	}
}
?>
