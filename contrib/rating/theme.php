<?php

class RatingsTheme extends Themelet {
	public function display_rater($page, $image_id) {
		$i_image_id = int_escape($image_id);
		$html = "
			<form action='".make_link("rating/set")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='radio' name='rating' value='s' id='s'><label for='s'>Safe</label>
				<input type='radio' name='rating' value='q' id='q'><label for='q'>Questionable</label>
				<input type='radio' name='rating' value='e' id='e'><label for='e'>Explicit</label>
				<input type='submit' value='Set' />
			</form>
		";
		$page->add_block(new Block(null, $html, "main", 45));
	}
}

?>
