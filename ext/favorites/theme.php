<?php

class FavoritesTheme extends Themelet {
	public function get_voter_html(Image $image, $is_favorited) {
		$i_image_id = int_escape($image->id);
		$name  = $is_favorited ? "unset" : "set";
		$label = $is_favorited ? "Un-Favorite" : "Favorite";
		$html  = "
			".make_form(make_link("change_favorite"))."
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='favorite_action' value='$name'>
			<input type='submit' value='$label'>
			</form>
		";

		return $html;
	}

	public function display_people($username_array) {
		global $page;

		$i_favorites = count($username_array);
		$html = "$i_favorites people:";

		reset($username_array); // rewind to first element in array.
		
		foreach($username_array as $row) {
			$username = html_escape($row);
			$html .= "<br><a href='".make_link("user/$username")."'>$username</a>";
		}

		$page->add_block(new Block("Favorited By", $html, "left", 25));
	}
}


