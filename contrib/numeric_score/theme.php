<?php

class NumericScoreTheme extends Themelet {
	public function display_voter($page, $image_id, $score) {
		$i_image_id = int_escape($image_id);
		$i_score = int_escape($score);
		
		$html = "
			<table style='width: 400px;'>
				<tr>
					<td>Current score is $i_score</td>
					<td>
			<form action='".make_link("numeric_score/vote")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='hidden' name='score' value='1'>
				<input type='submit' value='Vote Up' />
			</form>
					</td>
					<td>
			<form action='".make_link("numeric_score/vote")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='hidden' name='score' value='-1'>
				<input type='submit' value='Vote Down' />
			</form>
					</td>
				</tr>
			</table>
		";
		$page->add_block(new Block(null, $html, "main", 7));
	}
}

?>
