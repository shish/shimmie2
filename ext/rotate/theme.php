<?php

class RotateImageTheme extends Themelet {
	/*
	 * Display a link to rotate an image
	 */
	public function get_rotate_html(/*int*/ $image_id) {
		global $user, $config;

		$i_image_id = int_escape($image_id);
		
		$html = "
			".make_form(make_link("rotate"),'POST',false,'rotate_image')."
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='submit' value='Rotate' id='rotate_image_submit' />
			</form>
		";
		
		return $html;
	}
	
	public function display_rotate_error(Page $page, /*string*/ $title, /*string*/ $message) {
		$page->set_title("Rotate Image");
		$page->set_heading("Rotate Image");
		$page->add_block(new NavBlock());
		$page->add_block(new Block($title, $message));
	}
	
	public function display_rotate_page(Page $page, /*int*/ $image_id) {
		global $config;
		
		$default_deg = $config->get_int('rotate_default_deg');
		
		$image = Image::by_id($image_id);
		$thumbnail = $this->build_thumb_html($image, null);
		
		$html = "<div style='clear:both;'></div>
				<p>Rotate Image ID ".$image_id."<br>".$thumbnail."</p>
				<p>Please note: You will have to refresh the image page, or empty your browser cache.</p>
				<p>Enter the degrees to rotate the image of, counterclockwise.</p><br>"
				.make_form(make_link('rotate/'.$image_id), 'POST', $multipart=True,'form_rotate')."
				<input type='hidden' name='image_id' value='$image_id'>
				<table id='large_upload_form'>
					<tr><td>Degrees</td><td colspan='3'><input id='rotate_deg' name='rotate_deg' type='text' value='".$default_deg."'></td></tr>
					<tr><td colspan='4'><input id='rotatebutton' type='submit' value='Rotate'></td></tr>
				</table>
			</form>
		";
	
		$page->set_title("Rotate Image");
		$page->set_heading("Rotate Image");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Rotate Image", $html, "main", 20));
	
	}
}
?>
