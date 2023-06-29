<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\emptyHTML;
use function MicroHTML\{DIV,INPUT,P,PRE,SPAN,TABLE,TD,TH,TR};

class RatingsTheme extends Themelet
{
    public function get_selection_rater_html(string $name = "rating", array $ratings = [], array $selected_options = []): HTMLElement
    {
        return $this->build_selector($name, !empty($ratings) ? $ratings : Ratings::get_ratings_dict(), required: true, selected_options: $selected_options);
    }

    public function get_rater_html(int $image_id, string $rating, bool $can_rate): HTMLElement
    {
        $human_rating = Ratings::rating_to_human($rating);

        $html = TR(TH("Rating"));

        if ($can_rate) {
            $selector = $this->get_selection_rater_html(selected_options: [$rating]);

            $html->appendChild(TD(
                SPAN(["class"=>"view"], $human_rating),
                SPAN(["class"=>"edit"], $selector)
            ));
        } else {
            $html->appendChild(TD($human_rating));
        }

        return $html;
    }

    public function display_form(array $current_ratings)
    {
        global $page;

        $html = make_form_microhtml(make_link("admin/update_ratings"));

        $html->appendChild(TABLE(
            ["class"=>"form"],
            TR(TH("Change"), TD($this->get_selection_rater_html("rating_old", $current_ratings))),
            TR(TH("To"), TD($this->get_selection_rater_html("rating_new"))),
            TR(TD(["colspan"=>"2"], INPUT(["type"=>"submit", "value"=>"Update"])))
        ));

        $page->add_block(new Block("Update Ratings", $html));
    }

    public function get_help_html(array $ratings): HTMLElement
    {
        $output = emptyHTML(
            P("Search for posts with one or more possible ratings."),
            DIV(
                ["class"=>"command_example"],
                PRE("rating:" . $ratings[0]->search_term),
                P("Returns posts with the " . $ratings[0]->name . " rating.")
            ),
            P("Ratings can be abbreviated to a single letter as well."),
            DIV(
                ["class"=>"command_example"],
                PRE("rating:" . $ratings[0]->code),
                P("Returns posts with the " . $ratings[0]->name . " rating.")
            ),
            P("If abbreviations are used, multiple ratings can be searched for."),
            DIV(
                ["class"=>"command_example"],
                PRE("rating:" . $ratings[0]->code . $ratings[1]->code),
                P("Returns posts with the " . $ratings[0]->name . " or " . $ratings[1]->name . " rating.")
            ),
            P("Available ratings:")
        );

        $table = TABLE(TR(TH("Name"),TH("Search Term"),TH("Abbreviation")));

        foreach ($ratings as $rating) {
            $table->appendChild(TR(TD($rating->name),TD($rating->search_term),TD($rating->code)));
        }

        $output->appendChild($table);

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
                                ".$this->build_selector("ratings", selected_options: $selected_ratings, multiple: true, options: $available_ratings)."
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
