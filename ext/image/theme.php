<?php
class ImageIOTheme {
	/*
	 * Display a link to delete an image
	 * (Added inline Javascript to confirm the deletion)
	 *
	 * $image_id = the image to delete
	 */
	public function get_deleter_html($image_id) {
		global $user;
		global $config;

		$i_image_id = int_escape($image_id);
		
		if($config->get_bool("jquery_confirm")) {
			$html = "
				".make_form(make_link("image_admin/delete"),'POST',false,'delete_image')."
					<input type='hidden' name='image_id' value='$i_image_id' />
					<input type='submit' value='Delete' id='delete_image_submit' />
				</form>
			";
		} else {
			$html = "
				".make_form(make_link("image_admin/delete"))."
					<input type='hidden' name='image_id' value='$i_image_id' />
					<input type='submit' value='Delete' onclick='return confirm(\"Delete the image?\");' />
				</form>
			";
		}
		return $html;
	}
}
?>
