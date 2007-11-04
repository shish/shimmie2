<?php

class RatingsTheme extends Themelet {
	public function display_rater($page, $image_id, $rating) {
		$i_image_id = int_escape($image_id);
		$s_checked = $rating == 's' ? " checked" : "";
		$q_checked = $rating == 'q' ? " checked" : "";
		$e_checked = $rating == 'e' ? " checked" : "";
		$html = "
			<form action='".make_link("rating/set")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='radio' name='rating' value='s' id='s'$s_checked><label for='s'>Safe</label>
				<input type='radio' name='rating' value='q' id='q'$q_checked><label for='q'>Questionable</label>
				<input type='radio' name='rating' value='e' id='e'$e_checked><label for='e'>Explicit</label>
				<input type='submit' value='Set' />
			</form>
		";
		$page->add_block(new Block(null, $html, "main", 7));
	}

	public function rating_to_name($rating) {
		switch($rating) {
			case 's': return "Safe";
			case 'q': return "Questionable";
			case 'e': return "Explicit";
			default: return "Unknown";
		}
	}
}

?>
