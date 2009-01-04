<?php

class TextScoreTheme extends Themelet {
	public function get_scorer_html($image) {
		$i_image_id = int_escape($image->id);

		$s_score = $this->score_to_name($image->text_score);
		$html = "
			Current score is \"$s_score\"
			<br/>
			<input type='hidden' name='image_id' value='$i_image_id' />
			<input type='radio' name='text_score__score' value='-2' id='-2'><label for='-2'>Delete</label>
			<input type='radio' name='text_score__score' value='-1' id='-1'><label for='-1'>Bad</label>
			<input type='radio' name='text_score__score' value='0'  id='0' ><label for='0' >Ok</label>
			<input type='radio' name='text_score__score' value='1'  id='1' ><label for='1' >Good</label>
			<input type='radio' name='text_score__score' value='2'  id='2' ><label for='2' >Favourite</label>
		";
		return $html;
	}

	public function score_to_name($score) {
		$words = array();
		$words[-2] = "Delete";
		$words[-1] = "Bad";
		$words[ 0] = "Ok";
		$words[ 1] = "Good";
		$words[ 2] = "Favourite";
		$s_score = $words[$score];
		return $s_score;
	}
}

?>
