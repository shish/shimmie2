<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\A;
use function MicroHTML\BR;
use function MicroHTML\DIV;
use function MicroHTML\INPUT;
use function MicroHTML\SPAN;
use function MicroHTML\SUP;
use function MicroHTML\TEXTAREA;
use function MicroHTML\emptyHTML;
use function MicroHTML\rawHTML;

class CommentListTheme extends Themelet
{
    private bool $show_anon_id = false;
    private int $anon_id = 1;
    /** @var array<string,int> */
    private array $anon_map = [];

    /**
     * Display a page with a list of images, and for each image, the image's comments.
     *
     * @param array<array{0: Image, 1: Comment[]}> $images
     */
    public function display_comment_list(array $images, int $page_number, int $total_pages, bool $can_post): void
    {
        global $config, $page, $user;

        // parts for the whole page
        $prev = $page_number - 1;
        $next = $page_number + 1;

        $h_prev = ($page_number <= 1) ? "Prev" :
            '<a href="'.make_link('comment/list/'.$prev).'">Prev</a>';
        $h_index = "<a href='".make_link()."'>Index</a>";
        $h_next = ($page_number >= $total_pages) ? "Next" :
            '<a href="'.make_link('comment/list/'.$next).'">Next</a>';

        $nav = $h_prev.' | '.$h_index.' | '.$h_next;

        $page->set_title("Comments");
        $page->add_block(new Block("Navigation", rawHTML($nav), "left", 0));
        $this->display_paginator($page, "comment/list", null, $page_number, $total_pages);

        // parts for each image
        $position = 10;

        $comment_limit = $config->get_int(CommentConfig::LIST_COUNT, 10);
        $comment_captcha = $config->get_bool(CommentConfig::CAPTCHA);

        foreach ($images as $pair) {
            $image = $pair[0];
            $comments = $pair[1];

            $thumb_html = $this->build_thumb($image);
            $comment_html = "";

            $comment_count = count($comments);
            if ($comment_limit > 0 && $comment_count > $comment_limit) {
                $comment_html .= "<p>showing $comment_limit of $comment_count comments</p>";
                $comments = array_slice($comments, negative_int($comment_limit));
                $this->show_anon_id = false;
            } else {
                $this->show_anon_id = true;
            }
            $this->anon_id = 1;
            foreach ($comments as $comment) {
                $comment_html .= $this->comment_to_html($comment);
            }
            if (!$user->is_anonymous()) {
                if ($can_post) {
                    $comment_html .= $this->build_postbox($image->id);
                }
            } else {
                if ($can_post) {
                    if (!$comment_captcha) {
                        $comment_html .= $this->build_postbox($image->id);
                    } else {
                        $link = make_link("post/view/".$image->id);
                        $comment_html .= "<a href='$link'>Add Comment</a>";
                    }
                }
            }

            $html  = '
				<div class="comment_big_list">
					'.$thumb_html.'
					<div class="comment_list">'.$comment_html.'</div>
				</div>
			';

            $page->add_block(new Block($image->id.': '.$image->get_tag_list(), rawHTML($html), "main", $position++, "comment-list-list"));
        }
    }

    public function display_admin_block(): void
    {
        global $page;

        $html = '
			Delete comments by IP.

			<br><br>'.make_form(make_link("comment/bulk_delete"))."
				<table class='form'>
					<tr><th>IP&nbsp;Address</th> <td><input type='text' name='ip' size='15'></td></tr>
					<tr><td colspan='2'><input type='submit' value='Delete'></td></tr>
				</table>
			</form>
		";
        $page->add_block(new Block("Mass Comment Delete", rawHTML($html)));
    }

    /**
     * Add some comments to the page, probably in a sidebar.
     *
     * @param Comment[] $comments An array of Comment objects to be shown
     */
    public function display_recent_comments(array $comments): void
    {
        global $page;
        $this->show_anon_id = false;
        $html = "";
        foreach ($comments as $comment) {
            $html .= $this->comment_to_html($comment, true);
        }
        $html .= "<a class='more' href='".make_link("comment/list")."'>Full List</a>";
        $page->add_block(new Block("Comments", rawHTML($html), "left", 70, "comment-list-recent"));
    }

    /**
     * Show comments for an image.
     *
     * @param Comment[] $comments
     */
    public function display_image_comments(Image $image, array $comments, bool $postbox): void
    {
        global $page;
        $this->show_anon_id = true;
        $html = "";
        foreach ($comments as $comment) {
            $html .= $this->comment_to_html($comment);
        }
        if ($postbox) {
            $html .= $this->build_postbox($image->id);
        }
        $page->add_block(new Block("Comments", rawHTML($html), "main", 30, "comment-list-image"));
    }

    /**
     * Show comments made by a user.
     *
     * @param Comment[] $comments
     */
    public function display_recent_user_comments(array $comments, User $user): void
    {
        global $page;
        $html = "";
        foreach ($comments as $comment) {
            $html .= $this->comment_to_html($comment, true);
        }
        if (empty($html)) {
            $html = '<p>No comments by this user.</p>';
        } else {
            $html .= "<p><a href='".make_link("comment/beta-search/{$user->name}/1")."'>More</a></p>";
        }
        $page->add_block(new Block("Comments", rawHTML($html), "left", 70, "comment-list-user"));
    }

    /**
     * @param Comment[] $comments
     */
    public function display_all_user_comments(array $comments, int $page_number, int $total_pages, User $user): void
    {
        global $page;

        $html = "";
        foreach ($comments as $comment) {
            $html .= $this->comment_to_html($comment, true);
        }
        if (empty($html)) {
            $html = '<p>No comments by this user.</p>';
        }
        $page->add_block(new Block("Comments", rawHTML($html), "main", 70, "comment-list-user"));

        $prev = $page_number - 1;
        $next = $page_number + 1;

        $h_prev = ($page_number <= 1) ? "Prev" : "<a href='$prev'>Prev</a>";
        $h_index = "<a href='".make_link()."'>Index</a>";
        $h_next = ($page_number >= $total_pages) ? "Next" : "<a href='$next'>Next</a>";

        $page->set_title("{$user->name}'s comments");
        $page->add_block(new Block("Navigation", rawHTML($h_prev.' | '.$h_index.' | '.$h_next), "left", 0));
        $this->display_paginator($page, "comment/beta-search/{$user->name}", null, $page_number, $total_pages);
    }

    protected function comment_to_html(Comment $comment, bool $trim = false): HTMLElement
    {
        global $config, $user;

        if ($comment->owner_id == $config->get_int(UserAccountsConfig::ANON_ID)) {
            $anoncode = "";
            $anoncode2 = "";
            if ($this->show_anon_id) {
                $anoncode = SUP($this->anon_id);
                if (!array_key_exists($comment->poster_ip, $this->anon_map)) {
                    $this->anon_map[$comment->poster_ip] = $this->anon_id;
                }
                #if($user->can(UserAbilities::VIEW_IP)) {
                #$style = " style='color: ".$this->get_anon_colour($comment->poster_ip).";'";
                if ($user->can(IPBanPermission::VIEW_IP) || $config->get_bool(CommentConfig::SHOW_REPEAT_ANONS, false)) {
                    if ($this->anon_map[$comment->poster_ip] != $this->anon_id) {
                        $anoncode2 = SUP("(" . $this->anon_map[$comment->poster_ip] . ")");
                    }
                }
            }
            $userlink = SPAN(["class" => "username"], $comment->owner_name, $anoncode, $anoncode2);
            $this->anon_id++;
        } else {
            $userlink = A(["class" => "username", "href" => make_link("user/{$comment->owner_name}")], $comment->owner_name);
        }

        $tfe = send_event(new TextFormattingEvent($comment->comment));
        if ($trim) {
            $html = DIV(
                ["class" => "comment"],
                $userlink,
                ": ",
                truncate($tfe->stripped, 50),
                A(["href" => make_link("post/view/{$comment->image_id}", null, "c{$comment->comment_id}")], " >>>")
            );
        } else {
            /** @var BuildAvatarEvent $bae */
            $bae = send_event(new BuildAvatarEvent($comment->get_owner()));
            $html = DIV(
                ["class" => "comment", "id" => "c{$comment->comment_id}"],
                DIV(
                    ["class" => "info"],
                    emptyHTML(
                        $bae->html ? emptyHTML($bae->html, BR()) : null
                    ),
                    emptyHTML(
                        rawHTML(autodate($comment->posted)),
                        " - ",
                        A(["href" => "javascript:replyTo({$comment->image_id}, {$comment->comment_id}, '{$comment->owner_name}')"], "Reply"),
                    ),
                    emptyHTML(
                        $user->can(IPBanPermission::VIEW_IP) ? rawHTML("<br>".show_ip($comment->poster_ip, "Comment posted {$comment->posted}")) : null,
                        $user->can(CommentPermission::DELETE_COMMENT) ? emptyHTML(" - ", $this->delete_link($comment->comment_id, $comment->image_id, $comment->owner_name, $tfe->stripped)) : null,
                    ),
                ),
                $userlink,
                ": ",
                rawHTML($tfe->formatted)
            );
        }
        return $html;
    }

    protected function delete_link(int $comment_id, int $image_id, string $owner, string $text): HTMLElement
    {
        $comment_preview = truncate($text, 50);
        $j_delete_confirm_message = json_encode("Delete comment by {$owner}:\n$comment_preview") ?: json_encode("Delete <corrupt comment>");
        return A([
            "onclick" => "return confirm($j_delete_confirm_message);",
            "href" => make_link("comment/delete/$comment_id/$image_id"),
        ], "Del");
    }

    protected function build_postbox(int $image_id): HTMLElement
    {
        global $config;

        $hash = CommentList::get_hash();
        $h_captcha = $config->get_bool(CommentConfig::CAPTCHA) ? Captcha::get_html() : "";

        return DIV(
            ["class" => "comment comment_add"],
            SHM_SIMPLE_FORM(
                "comment/add",
                INPUT(["type" => "hidden", "name" => "image_id", "value" => $image_id]),
                INPUT(["type" => "hidden", "name" => "hash", "value" => $hash]),
                TEXTAREA(["id" => "comment_on_$image_id", "name" => "comment", "rows" => 5, "cols" => 50]),
                rawHTML($h_captcha),
                BR(),
                INPUT(["type" => "submit", "value" => "Post Comment"])
            ),
        );
    }

    public function get_help_html(): string
    {
        return '<p>Search for posts containing a certain number of comments, or comments by a particular individual.</p>
        <div class="command_example">
        <code>comments=1</code>
        <p>Returns posts with exactly 1 comment.</p>
        </div>
        <div class="command_example">
        <code>comments>0</code>
        <p>Returns posts with 1 or more comments. </p>
        </div>
        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =.</p>
        <div class="command_example">
        <code>commented_by:username</code>
        <p>Returns posts that have been commented on by "username". </p>
        </div>
        <div class="command_example">
        <code>commented_by_userno:123</code>
        <p>Returns posts that have been commented on by user 123. </p>
        </div>
        ';
    }
}
