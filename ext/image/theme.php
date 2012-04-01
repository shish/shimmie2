<?php
class ImageIOTheme {
	/**
	 * Display a link to delete an image
	 * (Added inline Javascript to confirm the deletion)
	 *
	 * @param $image_id The image to delete
	 */
	public function get_deleter_html(/*int*/ $image_id) {
		global $config;

		$html = "
			".make_form(make_link("image/delete"))."
				<input type='hidden' name='image_id' value='$image_id' />
				<input type='submit' value='Delete' onclick='return confirm(\"Delete the image?\");' />
			</form>
		";
		
		return $html;
	}
	
	/**
	 * Display link to replace the image
	 *
	 * @param $image_id The image to replace
	 */
	public function get_replace_html(/*int*/ $image_id) {
		$html = make_form(make_link("image/replace"))."
					<input type='hidden' name='image_id' value='$image_id' />
					<input type='submit' value='Replace' />
				</form>";
		return $html;
	}
}
?>
