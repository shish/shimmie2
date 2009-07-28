<?php

class FavoritesTheme extends Themelet {
	public function get_voter_html($image, $is_favorited) {
		$i_image_id = int_escape($image->id);
		$i_favorites = int_escape($image->favorites);
		
		global $user;
		$username = $user->name;
		
		$html = "
			Favorites: $i_favorites
			<br>
			<br>";
			
			if (!$is_favorited)
			{
				$html .= "<p><form action='".make_link("change_favorite")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='favorite_action' value='set'>
			<input type='submit' value='Favorite'>
			</form></p>";
			}
			else
			{			
				$html .= "<p><form action='".make_link("change_favorite")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='favorite_action' value='unset'>
			<input type='submit' value='Un-Favorite'>
			</form></p>";
			}
			
			$pos = strpos($username, ' ');
			if ($pos === false) {
				$html .= "<br><a href='".make_link("post/list/favorited_by=$username/1")."'>Show my favorites</a><br><br>";
			} else {
				$userid = $user->id;
				$html .= "<br><a href='".make_link("post/list/favorited_by_userno=$userid/1")."'>Show my favorites</a><br><br>";
			}
		return $html;
	}
	
	public function show_anonymous_html($image) {
		$i_image_id = int_escape($image->id);
		$i_favorites = int_escape($image->favorites);
		
		$html = "
			Favorites: $i_favorites
			<br><br>";
		return $html;
	}
	
	public function show_favorite_marks($username_array) {
		$html = '';
	
		foreach ($username_array as $row) {
			$username = $row['name'];
			$html .= "<a href='".make_link("user/$username")."'>$username</a><br>";
		}
		
		if ($html == '') {
			$html = 'Not favorited yet';
		}
		
		return $html;

	}
	
}

?>
