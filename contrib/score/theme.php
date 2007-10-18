<?php

class ScoreTheme extends Themelet {
	public function display_rater($page, $image_id, $score) {
		$i_image_id = int_escape($image_id);
		$html = "
			<form action='".make_link("score/set")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='radio' name='score' value='--2' id='-2'><label for='-2'>Delete</label>
				<input type='radio' name='score' value='-1' id='-1'><label for='-1'>Bad</label>
				<input type='radio' name='score' value='0' id='0'><label for='0'>Ok</label>
				<input type='radio' name='score' value='1' id='1'><label for='1'>Good</label>
				<input type='radio' name='score' value='2' id='2'><label for='2'>Awesome</label>
				<input type='submit' value='Vote' />
			</form>
		";
		$page->add_block(new Block(null, $html, "main", 7));
	}
}

?>
