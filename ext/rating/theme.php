<?php

class RatingsTheme extends Themelet {
	/**
	 * @param int $image_id
	 * @param string $rating
	 * @return string
	 */
	public function get_rater_html(/*int*/ $image_id, /*string*/ $rating, /*bool*/ $can_rate) {
		$s_checked = $rating == 's' ? " checked" : "";
		$q_checked = $rating == 'q' ? " checked" : "";
		$e_checked = $rating == 'e' ? " checked" : "";
		$human_rating = Ratings::rating_to_human($rating);
		$html = "
			<tr>
				<th>Rating</th>
				<td>
		".($can_rate ? "
					<span class='view'>$human_rating</span>
					<span class='edit'>
						<input type='radio' name='rating' value='s' id='s'$s_checked><label for='s'>Safe</label>
						<input type='radio' name='rating' value='q' id='q'$q_checked><label for='q'>Questionable</label>
						<input type='radio' name='rating' value='e' id='e'$e_checked><label for='e'>Explicit</label>
					</span>
		" : "
					$human_rating
		")."
				</td>
			</tr>
		";
		return $html;
	}

	public function display_bulk_rater($terms) {
		global $page;
		$html = "
			".make_form(make_link("admin/bulk_rate"))."
				<input type='hidden' name='query' value='".html_escape($terms)."'>
				<select name='rating'>
					<option value='s'>Safe</option>
					<option value='q'>Questionable</option>
					<option value='e'>Explicit</option>
					<option value='u'>Unrated</option>
				</select>
				<input type='submit' value='Go'>
			</form>
		";
		$page->add_block(new Block("List Controls", $html, "left"));
	}
}


