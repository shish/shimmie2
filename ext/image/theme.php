<?php
class ImageIOTheme extends Themelet {
	/**
	 * Display a link to delete an image
	 * (Added inline Javascript to confirm the deletion)
	 */
	public function get_deleter_html(int $image_id): string {
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
	 */
	public function get_replace_html(int $image_id): string {
		$html = make_form(make_link("image/replace"))."
					<input type='hidden' name='image_id' value='$image_id' />
					<input type='submit' value='Replace' />
				</form>";
		return $html;
	}
}

