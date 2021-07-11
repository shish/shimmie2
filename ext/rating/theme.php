<?php declare(strict_types=1);

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
						".$this->get_selection_rater_html([$rating])."
					</span>
		" : "
					$human_rating
		")."
				</td>
			</tr>
		";
        return $html;
    }

    public function display_form(array $current_ratings)
    {
        global $page;

        $html = make_form(make_link("admin/update_ratings"))."<table class='form'>";

        $html .= "<tr><th>Change</th><td>".$this->build_selector("rating_old", $current_ratings, "required", true)."</td></tr>";
        $html .= "<tr><th>To</th><td>".$this->build_selector("rating_new", Ratings::get_ratings_dict(), "required", true)."</td></tr>";

        $html .= "<tr><td colspan='2'><input type='submit' value='Update'></td></tr></table>
        </form>\n";

        $page->add_block(new Block("Update Ratings", $html));
    }

    public function get_selection_rater_html(array $selected_options, bool $multiple = false): string
    {
        return $this->build_selector("rating", Ratings::get_ratings_dict(), ($multiple ? "multiple" : ""), false, $selected_options);
    }

    public function get_help_html(array $ratings): string
    {
        $output =  '<p>Search for posts with one or more possible ratings.</p>
        <div class="command_example">
        <pre>rating:'.$ratings[0]->search_term.'</pre>
        <p>Returns posts with the '.$ratings[0]->name.' rating.</p>
        </div>
        <p>Ratings can be abbreviated to a single letter as well</p>
        <div class="command_example">
        <pre>rating:'.$ratings[0]->code.'</pre>
        <p>Returns posts with the '.$ratings[0]->name.' rating.</p>
        </div>
        <p>If abbreviations are used, multiple ratings can be searched for.</p>
        <div class="command_example">
        <pre>rating:'.$ratings[0]->code.$ratings[1]->code.'</pre>
        <p>Returns posts with the '.$ratings[0]->name.' or '.$ratings[1]->name.' rating.</p>
        </div>
        <p>Available ratings:</p>
        <table>
        <tr><th>Name</th><th>Search Term</th><th>Abbreviation</th></tr>
        ';
        foreach ($ratings as $rating) {
            $output .= "<tr><td>{$rating->name}</td><td>{$rating->search_term}</td><td>{$rating->code}</td></tr>";
        }
        $output .= "</table>";
        return $output;
    }

    // This wasn't being used at all

    /* public function get_user_options(User $user, array $selected_ratings, array $available_ratings): string
    {
        $html = "
                <p>".make_form(make_link("user_admin/default_ratings"))."
                    <input type='hidden' name='id' value='$user->id'>
                    <table style='width: 300px;'>
                        <thead>
                            <tr><th colspan='2'></th></tr>
                        </thead>
                        <tbody>
                        <tr><td>This controls the default rating search results will be filtered by, and nothing else. To override in your search results, add rating:* to your search.</td></tr>
                            <tr><td>
                                ".$this->get_selection_rater_html($selected_ratings, true, $available_ratings)."
                            </td></tr>
                        </tbody>
                        <tfoot>
                            <tr><td><input type='submit' value='Save'></td></tr>
                        </tfoot>
                    </table>
                </form>
            ";
        return $html;
    } */
}
