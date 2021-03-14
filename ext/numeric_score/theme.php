<?php declare(strict_types=1);

class NumericScoreTheme extends Themelet
{
    public function get_voter(Image $image)
    {
        global $user, $page;
        $i_image_id = $image->id;
        if (is_string($image->numeric_score)) {
            $image->numeric_score = (int)$image->numeric_score;
        }
        $i_score = $image->numeric_score;

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
        if ($user->can(Permissions::EDIT_OTHER_VOTE)) {
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
        $page->add_block(new Block("Post Score", $html, "left", 20));
    }

    public function get_nuller(User $duser)
    {
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

    public function view_popular($images, $dte)
    {
        global $page, $config;

        $pop_images = "";
        foreach ($images as $image) {
            $pop_images .= $this->build_thumb_html($image)."\n";
        }

        $b_dte = make_link("popular_by_".$dte[3], date($dte[2], (strtotime('-1 '.$dte[3], strtotime($dte[0])))));
        $f_dte = make_link("popular_by_".$dte[3], date($dte[2], (strtotime('+1 '.$dte[3], strtotime($dte[0])))));

        $html = "\n".
            "<h3 style='text-align: center;'>\n".
            "	<a href='{$b_dte}'>&laquo;</a> {$dte[1]} <a href='{$f_dte}'>&raquo;</a>\n".
            "</h3>\n".
            "<br/>\n".$pop_images;


        $nav_html = "<a href=".make_link().">Index</a>";

        $page->set_heading($config->get_string(SetupConfig::TITLE));
        $page->add_block(new Block("Navigation", $nav_html, "left", 10));
        $page->add_block(new Block(null, $html, "main", 30));
    }


    public function get_help_html(): string
    {
        return '<p>Search for posts that have received numeric scores by the score or by the scorer.</p>
        <div class="command_example">
        <pre>score=1</pre>
        <p>Returns posts with a score of 1.</p>
        </div>
        <div class="command_example">
        <pre>score>0</pre>
        <p>Returns posts with a score of 1 or more.</p>
        </div>
        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =.</p>

        <div class="command_example">
        <pre>upvoted_by=username</pre>
        <p>Returns posts upvoted by "username".</p>
        </div>
        <div class="command_example">
        <pre>upvoted_by_id=123</pre>
        <p>Returns posts upvoted by user 123.</p>
        </div>
        <div class="command_example">
        <pre>downvoted_by=username</pre>
        <p>Returns posts downvoted by "username".</p>
        </div>
        <div class="command_example">
        <pre>downvoted_by_id=123</pre>
        <p>Returns posts downvoted by user 123.</p>
        </div>

        <div class="command_example">
        <pre>order:score_desc</pre>
        <p>Sorts the search results by score, descending.</p>
        </div>
        <div class="command_example">
        <pre>order:score_asc</pre>
        <p>Sorts the search results by score, ascending.</p>
        </div>
        ';
    }
}
