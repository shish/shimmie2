<?php

class NumericScoreTheme extends Themelet {
	public function get_voter_html(Image $image) {
		global $user;
		$i_image_id = int_escape($image->id);
		$i_score = int_escape($image->numeric_score);

		$html = "
			Current Score: $i_score

			<p><form action='".make_link("numeric_score_vote")."' method='POST'>
			".$user->get_auth_html()."
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='vote' value='up'>
			<input type='submit' value='Vote Up'>
			</form>

			<form action='".make_link("numeric_score_vote")."' method='POST'>
			".$user->get_auth_html()."
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='vote' value='null'>
			<input type='submit' value='Remove Vote'>
			</form>

			<form action='".make_link("numeric_score_vote")."' method='POST'>
			".$user->get_auth_html()."
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='vote' value='down'>
			<input type='submit' value='Vote Down'>
			</form>
		";
		if($user->is_admin()) {
			$html .= "
			<form action='".make_link("numeric_score/remove_votes_on")."' method='POST'>
			".$user->get_auth_html()."
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='submit' value='Remove All Votes'>
			</form>

			<p><a href='".make_link("numeric_score_votes/$i_image_id")."'>See All Votes</a>
			";
		}
		return $html;
	}

	public function get_nuller_html(User $duser) {
		global $user;
		$html = "
			<form action='".make_link("numeric_score/remove_votes_by")."' method='POST'>
			".$user->get_auth_html()."
			<input type='hidden' name='user_id' value='{$duser->id}'>
			<input type='submit' value='Delete all votes by this user'>
			</form>
		";
		return $html;
	}

	public function view_popular($images, $dte) {
		global $user, $page;

		$pop_images = '';
		foreach($images as $image) {
			$thumb_html = $this->build_thumb_html($image);
			$pop_images .= '<span class="thumb">'.
				'<a href="$image_link">'.$thumb_html.'</a>'.
				'</span>';
		}

		$nav_html = "
			<a href=".make_link().">Index</a>
		";

		$page->add_block(new Block("Navigation", $nav_html, "left", 10));
		$page->add_block(new Block("Most popular images of: ".$dte, $pop_images, "main", 30));
	}
}

?>
