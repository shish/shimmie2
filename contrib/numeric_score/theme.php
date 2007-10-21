<?php

class NumericScoreTheme extends Themelet {
	public function display_voter($page, $image_id, $score) {
		$i_image_id = int_escape($image_id);
		$i_score = int_escape($score) / 2;
		
		$html = "
			Current score is $i_score
			<br>
			<form action='".make_link("numeric_score/vote")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='hidden' name='score' value='-2'>
				<input type='submit' value='Vote Down' />
			</form>
			<form action='".make_link("numeric_score/vote")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='hidden' name='score' value='2'>
				<input type='submit' value='Vote Up' />
			</form>
		";
		$page->add_block(new Block(null, $html, "main", 7));
	}
}

?>
