<?php

class RatingsTheme extends Themelet
{
    public function get_rater_html(int $image_id, string $rating, bool $can_rate): string
    {
        $human_rating = Ratings::rating_to_human($rating);
        $html = "
			<tr>
				<th>Rating</th>
				<td>
		".($can_rate ? "
					<span class='view'>$human_rating</span>
					<span class='edit'>
						".$this->get_selection_rater_html($rating)."
					</span>
		" : "
					$human_rating
		")."
				</td>
			</tr>
		";
        return $html;
    }

    // public function display_bulk_rater(string $terms)
    // {
    //     global $page;
    //     $html = "
	// 		".make_form(make_link("admin/bulk_rate"))."
	// 			<input type='hidden' name='query' value='".html_escape($terms)."'>
	// 			<select name='rating'>
	// 				<option value='s'>Safe</option>
	// 				<option value='q'>Questionable</option>
	// 				<option value='e'>Explicit</option>
	// 				<option value='u'>Unrated</option>
	// 			</select>
	// 			<input type='submit' value='Go'>
	// 		</form>
	// 	";
    //     $page->add_block(new Block("List Controls", $html, "left"));
    // }

    public function get_selection_rater_html(String $selected_option, String $id = "rating") {
        global $_shm_ratings;

		$output = "<select name='".$id."'>";
		$options = array_values($_shm_ratings);
		usort($options, function($a, $b) {
			return $a->order <=> $b->order;
		});
		
		foreach($options as $option) {			
			$output .= "<option value='".$option->code."' ".($selected_option==$option->code ? "selected='selected'": "").">".$option->name."</option>";
		}
		return $output."</select>";
    }
}
