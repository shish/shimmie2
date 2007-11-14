<?php

class NumericScoreTheme extends Themelet {
	public function get_voter_html($image) {
		$i_image_id = int_escape($image->id);
		$i_score = int_escape($image->numeric_score);
		
		$html = "
			<table style='width: 400px;'>
				<tr>
					<td>Current score is $i_score</td>
					<td>
					<!--
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
			</form>-->
					</td>
				</tr>
			</table>
		";
		return $html;
	}
}

?>
