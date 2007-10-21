<?php

class ScoreTheme extends Themelet {
	public function display_scorer($page, $image_id, $score) {
		$i_image_id = int_escape($image_id);
		
		$words = array();
		$words[-2] = "Delete";
		$words[-1] = "Bad";
		$words[ 0] = "Ok";
		$words[ 1] = "Good";
		$words[ 2] = "Favourite";
		$s_score = $words[$score];
		$html = "
			Current score is \"$s_score\"
			<br><form action='".make_link("score_text/vote")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='radio' name='score' value='-2' id='-2'><label for='-2'>Delete</label>
				<input type='radio' name='score' value='-1' id='-1'><label for='-1'>Bad</label>
				<input type='radio' name='score' value='0'  id='0' ><label for='0' >Ok</label>
				<input type='radio' name='score' value='1'  id='1' ><label for='1' >Good</label>
				<input type='radio' name='score' value='2'  id='2' ><label for='2' >Favourite</label>
				<input type='submit' value='Vote' />
			</form>
		";
		$page->add_block(new Block(null, $html, "main", 7));
	}
}

?>
