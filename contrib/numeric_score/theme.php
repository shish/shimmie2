<?php

class NumericScoreTheme extends Themelet {
	public function get_voter_html($image) {
		$i_image_id = int_escape($image->id);
		$i_score = int_escape($image->numeric_score);
		
		$html = "
			<tr>
				<td>Score ($i_score)</td>
				<td>
					<input type='radio' name='numeric_score' value='u' id='u'><label for='u'>Up</label>
					<input type='radio' name='numeric_score' value='n' id='n' checked><label for='n'>Keep</label>
					<input type='radio' name='numeric_score' value='d' id='d'><label for='d'>Down</label>
				</td>
			</tr>
		";
		return $html;
	}
}

?>
