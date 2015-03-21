<?php
class ImageIOTheme extends Themelet {
	/**
	 * Display a link to delete an image
	 * (Added inline Javascript to confirm the deletion)
	 *
	 * @param $image_id integer The image to delete
	 * @return string
	 */
	public function get_deleter_html(/*int*/ $image_id) {
		global $config;

		$html = "
			".make_form("image/delete", "POST", array(), TRUE)."
				<input type='hidden' name='image_id' value='$image_id' />
				<input type='submit' value='Delete' onclick='return confirm(\"Delete the image?\");' />
			</form>
		";
		
		return $html;
	}

	/**
	 * Display link to replace the image
	 *
	 * @param $image_id integer The image to replace
	 * @return string
	 */
	public function get_replace_html(/*int*/ $image_id) {
		$html = make_form("image/replace", "POST", array(), TRUE)."
					<input type='hidden' name='image_id' value='$image_id' />
					<input type='submit' value='Replace' />
				</form>";
		return $html;
	}
}

