<?php

class NumericScoreTheme extends Themelet {
	public function get_voter_html($image) {
		$i_image_id = int_escape($image->id);
		$i_score = int_escape($image->numeric_score);
		
		$html = "
			Current Score: $i_score

			<p><form action='".make_link("numeric_score_vote")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='vote' value='up'>
			<input type='submit' value='Vote Up'>
			</form>

			<p><form action='".make_link("numeric_score_vote")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='vote' value='down'>
			<input type='submit' value='Vote Down'>
			</form>
		";
		return $html;
	}
}

?>
