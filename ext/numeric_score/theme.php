<?php

class NumericScoreTheme extends Themelet {
	public function get_voter(Image $image) {
		global $user, $page;
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
		if($user->can("edit_other_vote")) {
			$html .= "
			<form action='".make_link("numeric_score/remove_votes_on")."' method='POST'>
			".$user->get_auth_html()."
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='submit' value='Remove All Votes'>
			</form>

			<br><div id='votes-content'>
				<a
					href='".make_link("numeric_score_votes/$i_image_id")."'
					onclick='$(\"#votes-content\").load(\"".make_link("numeric_score_votes/$i_image_id")."\"); return false;'
				>See All Votes</a>
			</div>
			";
		}
		$page->add_block(new Block("Image Score", $html, "left", 20));
	}

	public function get_nuller(User $duser) {
		global $user, $page;
		$html = "
			<form action='".make_link("numeric_score/remove_votes_by")."' method='POST'>
			".$user->get_auth_html()."
			<input type='hidden' name='user_id' value='{$duser->id}'>
			<input type='submit' value='Delete all votes by this user'>
			</form>
		";
		$page->add_block(new Block("Votes", $html, "main", 80));
	}

	public function view_popular($images, $dte) {
		global $page, $config;

		$pop_images = "";
		foreach($images as $image) {
			$pop_images .= $this->build_thumb_html($image)."\n";
		}

		$b_dte = make_link("popular_by_".$dte[3]."?".date($dte[2], (strtotime('-1 '.$dte[3], strtotime($dte[0])))));
		$f_dte = make_link("popular_by_".$dte[3]."?".date($dte[2], (strtotime('+1 '.$dte[3], strtotime($dte[0])))));

		$html = "\n".
			"<center>\n".
			"	<h3>\n".
			"		<a href='{$b_dte}'>&laquo;</a> {$dte[1]} <a href='{$f_dte}'>&raquo;</a>\n".
			"	</h3>\n".
			"</center>\n".
			"<br/>\n".$pop_images;


		$nav_html = "<a href=".make_link().">Index</a>";

		$page->set_heading($config->get_string('title'));
		$page->add_block(new Block("Navigation", $nav_html, "left", 10));
		$page->add_block(new Block(null, $html, "main", 30));
	}
}


