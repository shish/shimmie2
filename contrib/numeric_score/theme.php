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
}

?>
