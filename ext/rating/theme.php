<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\emptyHTML;
use function MicroHTML\{A,P,TABLE,TD,TH,TR};

class RatingsTheme extends Themelet
{
    /**
     * @param array<string, string> $ratings
     * @param string[] $selected_options
     */
    public function get_selection_rater_html(string $name = "rating", array $ratings = [], array $selected_options = []): HTMLElement
    {
        return SHM_SELECT($name, !empty($ratings) ? $ratings : Ratings::get_ratings_dict(), required: true, selected_options: $selected_options);
    }

    public function get_image_rater_html(int $image_id, string $rating, bool $can_rate): HTMLElement
    {
        return SHM_POST_INFO(
            "Rating",
            A(["href" => search_link(["rating=$rating"])], Ratings::rating_to_human($rating)),
            $can_rate ? $this->get_selection_rater_html("rating", selected_options: [$rating]) : null
        );
    }

    public function get_upload_specific_rater_html(string $suffix): HTMLElement
    {
        return TD($this->get_selection_rater_html(name:"rating{$suffix}", selected_options: ["?"]));
    }

    /**
     * @param array<string,string> $current_ratings
     */
    public function display_form(array $current_ratings): void
    {
        global $page;

        $table = TABLE(
            ["class" => "form"],
            TR(TH("Change"), TD($this->get_selection_rater_html("rating_old", $current_ratings))),
            TR(TH("To"), TD($this->get_selection_rater_html("rating_new"))),
            TR(TD(["colspan" => "2"], SHM_SUBMIT("Update")))
        );

        $page->add_block(new Block("Update Ratings", SHM_SIMPLE_FORM("admin/update_ratings", $table)));
    }

    /**
     * @param ImageRating[] $ratings
     */
    public function get_help_html(array $ratings): HTMLElement
    {
        $rating_rows = [TR(TH("Name"), TH("Search Term"), TH("Abbreviation"))];
        foreach ($ratings as $rating) {
            $rating_rows[] = TR(TD($rating->name), TD($rating->search_term), TD($rating->code));
        }

        return emptyHTML(
            P("Search for posts with one or more possible ratings."),
            SHM_COMMAND_EXAMPLE(
                "rating:" . $ratings[0]->search_term,
                "Returns posts with the " . $ratings[0]->name . " rating."
            ),
            P("Ratings can be abbreviated to a single letter as well."),
            SHM_COMMAND_EXAMPLE(
                "rating:" . $ratings[0]->code,
                "Returns posts with the " . $ratings[0]->name . " rating."
            ),
            P("If abbreviations are used, multiple ratings can be searched for."),
            SHM_COMMAND_EXAMPLE(
                "rating:" . $ratings[0]->code . $ratings[1]->code,
                "Returns posts with the " . $ratings[0]->name . " or " . $ratings[1]->name . " rating."
            ),
            P("Available ratings:"),
            TABLE(...$rating_rows)
        );
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
                                ".SHM_SELECT("ratings", selected_options: $selected_ratings, multiple: true, options: $available_ratings)."
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
