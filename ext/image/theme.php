<?php
class ImageIOTheme {
	/**
	 * Display a link to delete an image
	 * (Added inline Javascript to confirm the deletion)
	 *
	 * @param $image_id The image to delete
	 */
	public function get_deleter_html($image_id) {
		global $user;
		global $config;

		if($config->get_bool("jquery_confirm")) {
			$html = "
				".make_form(make_link("image_admin/delete"),'POST',false,'delete_image')."
					<input type='hidden' name='image_id' value='$image_id' />
					<input type='submit' value='Delete' id='delete_image_submit' />
				</form>
			";
		} else {
			$html = "
				".make_form(make_link("image_admin/delete"))."
					<input type='hidden' name='image_id' value='$image_id' />
					<input type='submit' value='Delete' onclick='return confirm(\"Delete the image?\");' />
				</form>
			";
		}
	}
	
	/**
	 * Display link to replace the image
	 *
	 * @param $image_id The image to replace
	 */
	public function get_deleter_html($image_id) {
	
		$html = "
				".make_form(make_link("image_admin/replace"))."
					<input type='hidden' name='image_id' value='$image_id' />
					<input type='submit' value='Replace' />
				</form>";
		
		return $html;
	}
}
?>
