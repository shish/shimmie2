<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, DIV, H3, INPUT, P, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

class NumericScoreTheme extends Themelet
{
    public function get_voter(Image $image): void
    {
        $vote_form = function (int $image_id, int $vote, string $text): HTMLElement {
            return SHM_SIMPLE_FORM(
                make_link("numeric_score/vote"),
                INPUT(['type' => 'hidden', 'name' => 'image_id', 'value' => $image_id]),
                INPUT(['type' => 'hidden', 'name' => 'vote', 'value' => $vote]),
                SHM_SUBMIT($text)
            );
        };
        $remove_votes = null;
        $voters = null;
        if (Ctx::$user->can(NumericScorePermission::EDIT_OTHER_VOTE)) {
            $remove_votes = SHM_SIMPLE_FORM(
                make_link("numeric_score/remove_votes_on"),
                INPUT(['type' => 'hidden', 'name' => 'image_id', 'value' => $image->id]),
                SHM_SUBMIT('Remove All Votes')
            );
            $voters = emptyHTML(
                BR(),
                DIV(
                    ["id" => "votes-content"],
                    A(
                        [
                            "href" => make_link("numeric_score/votes/$image->id"),
                            "onclick" => '$("#votes-content").load("'.make_link("numeric_score/votes/$image->id").'"); return false;',
                        ],
                        "See All Votes"
                    )
                ),
            );
        }
        $html = emptyHTML(
            $vote_form($image->id, 1, "Vote Up"),
            $vote_form($image->id, 0, "Remove Vote"),
            $vote_form($image->id, -1, "Vote Down"),
            $remove_votes,
            $voters
        );
        Ctx::$page->add_block(new Block("Post Score: " . $image['numeric_score'], $html, "left", 20, id: "Post_Scoreleft"));
    }

    public function get_nuller(User $duser): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("numeric_score/remove_votes_by"),
            INPUT(["type" => "hidden", "name" => "user_id", "value" => $duser->id]),
            SHM_SUBMIT("Delete all votes by this user")
        );
        Ctx::$page->add_block(new Block("Votes", $html, "main", 80));
    }

    /**
     * @param Image[] $images
     */
    public function view_popular(
        array $images,
        string $current,
        Url $b_dte,
        Url $f_dte,
    ): void {
        $pop_images = [];
        foreach ($images as $image) {
            $pop_images[] = $this->build_thumb($image);
        }

        $html = emptyHTML(
            H3(
                ["style" => "text-align: center;"],
                A(["href" => $b_dte], "<<"),
                " $current ",
                A(["href" => $f_dte], ">>")
            ),
            BR(),
            joinHTML("\n", $pop_images)
        );

        Ctx::$page->set_title("Popular Posts");
        Ctx::$page->add_block(new Block(null, $html, "main", 30));
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts that have received numeric scores by the score or by the scorer."),
            SHM_COMMAND_EXAMPLE("score=1", "Returns posts with a score of 1"),
            SHM_COMMAND_EXAMPLE("score>0", "Returns posts with a score of 1 or more"),
            P("Can use <, <=, >, >=, or =."),
            SHM_COMMAND_EXAMPLE("upvoted_by=username", "Returns posts upvoted by 'username'"),
            SHM_COMMAND_EXAMPLE("downvoted_by=username", "Returns posts downvoted by 'username'"),
            SHM_COMMAND_EXAMPLE("order=score_desc", "Returns posts ordered by score in descending order"),
            SHM_COMMAND_EXAMPLE("order=score_asc", "Returns posts ordered by score in ascending order")
        );
    }
}
