<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, DIV, INPUT, P, SPAN, SUP, TABLE, TD, TEXTAREA, TH, TR, emptyHTML};

use MicroHTML\HTMLElement;

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
        $page = Ctx::$page;
        $page->set_title("Comments");
        $page->set_navigation(
            make_link('comment/list/'.($page_number - 1)),
            make_link('comment/list/'.($page_number + 1))
        );
        $this->display_paginator("comment/list", null, $page_number, $total_pages);

        // parts for each image
        $position = 10;

        $comment_limit = Ctx::$config->get(CommentConfig::LIST_COUNT);

        foreach ($images as $pair) {
            $image = $pair[0];
            $comments = $pair[1];

            $comment_html = emptyHTML();

            $comment_count = count($comments);
            if ($comment_limit > 0 && $comment_count > $comment_limit) {
                $comment_html->appendChild(P("showing $comment_limit of $comment_count comments"));
                $comments = array_slice($comments, negative_int($comment_limit));
                $this->show_anon_id = false;
            } else {
                $this->show_anon_id = true;
            }
            $this->anon_id = 1;
            foreach ($comments as $comment) {
                $comment_html->appendChild($this->comment_to_html($comment));
            }
            $comment_html->appendChild($this->build_postbox($image->id));

            $html = DIV(
                ["class" => "comment_big_list"],
                $this->build_thumb($image),
                DIV(["class" => "comment_list"], $comment_html)
            );

            $page->add_block(new Block($image->id.': '.$image->get_tag_list(), $html, "main", $position++, "comment-list-list"));
        }
    }

    public function display_admin_block(): void
    {
        $html = DIV(
            "Delete comments by IP.",
            BR(),
            BR(),
            SHM_SIMPLE_FORM(
                make_link("comment/bulk_delete"),
                TABLE(
                    ["class" => "form"],
                    TR(
                        TH("IP Address"),
                        TD(INPUT(["type" => "text", "name" => "ip", "size" => 15]))
                    ),
                    TR(
                        TD(["colspan" => 2], INPUT(["type" => "submit", "value" => "Delete"]))
                    )
                )
            )
        );
        Ctx::$page->add_block(new Block("Mass Comment Delete", $html));
    }

    /**
     * Add some comments to the page, probably in a sidebar.
     *
     * @param Comment[] $comments An array of Comment objects to be shown
     */
    public function display_recent_comments(array $comments): void
    {
        $this->show_anon_id = false;
        $html = emptyHTML();
        foreach ($comments as $comment) {
            $html->appendChild($this->comment_to_html($comment, true));
        }
        $html->appendChild(A(["class" => "more", "href" => make_link("comment/list")], "Full List"));
        Ctx::$page->add_block(new Block("Comments", $html, "left", 70, "comment-list-recent"));
    }

    /**
     * Show comments for an image.
     *
     * @param Image $image
     * @param Comment[] $comments
     * @param bool $postbox
     * @param bool $comments_locked
     */
    public function display_image_comments(Image $image, array $comments, bool $postbox, bool $comments_locked): void
    {
        $this->show_anon_id = true;
        $html = emptyHTML();

        // Show lock status notice
        if ($comments_locked) {
            $html->appendChild(
                DIV(
                    ["class" => "comment_lock_status"],
                    P(["class" => "comment_locked_notice"], "🔒 Comments are locked on this post")
                )
            );
        }

        foreach ($comments as $comment) {
            $html->appendChild($this->comment_to_html($comment));
        }
        if ($postbox) {
            $html->appendChild($this->build_postbox($image->id));
        }
        Ctx::$page->add_block(new Block("Comments", $html, "main", 30, "comment-list-image"));
    }

    /**
     * Show comments made by a user.
     *
     * @param Comment[] $comments
     */
    public function display_recent_user_comments(array $comments, User $user): void
    {
        $html = emptyHTML();
        foreach ($comments as $comment) {
            $html->appendChild($this->comment_to_html($comment, true));
        }
        if (count($comments) === 0) {
            $html->appendChild(P("No comments by this user."));
        } else {
            $html->appendChild(P(A(["href" => make_link("comment/beta-search/{$user->name}/1")], "More")));
        }
        Ctx::$page->add_block(new Block("Comments", $html, "left", 70, "comment-list-user"));
    }

    /**
     * @param Comment[] $comments
     */
    public function display_all_user_comments(array $comments, int $page_number, int $total_pages, User $user): void
    {
        $html = emptyHTML();
        foreach ($comments as $comment) {
            $html->appendChild($this->comment_to_html($comment, true));
        }
        if (count($comments) === 0) {
            $html->appendChild(P("No comments by this user."));
        }
        Ctx::$page->add_block(new Block("Comments", $html, "main", 70, "comment-list-user"));
        Ctx::$page->set_title("{$user->name}'s comments");
        Ctx::$page->set_navigation(
            ($page_number <= 1) ? null : make_link("comment/beta-search/{$user->name}/" . ($page_number - 1)),
            ($page_number >= $total_pages) ? null : make_link("comment/beta-search/{$user->name}/" . ($page_number + 1)),
        );
        $this->display_paginator("comment/beta-search/{$user->name}", null, $page_number, $total_pages);
    }

    protected function comment_to_html(Comment $comment, bool $trim = false): HTMLElement
    {
        if ($comment->owner_id === Ctx::$config->get(UserAccountsConfig::ANON_ID)) {
            $anoncode = "";
            $anoncode2 = "";
            if ($this->show_anon_id) {
                $anoncode = SUP($this->anon_id);
                if (!array_key_exists($comment->poster_ip, $this->anon_map)) {
                    $this->anon_map[$comment->poster_ip] = $this->anon_id;
                }
                #if(Ctx::$user->can(UserAbilities::VIEW_IP)) {
                #$style = " style='color: ".$this->get_anon_colour($comment->poster_ip).";'";
                if (Ctx::$user->can(IPBanPermission::VIEW_IP) || Ctx::$config->get(CommentConfig::SHOW_REPEAT_ANONS)) {
                    if ($this->anon_map[$comment->poster_ip] !== $this->anon_id) {
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
                        SHM_DATE($comment->posted),
                        " - ",
                        A(["href" => "javascript:replyTo({$comment->image_id}, {$comment->comment_id}, '{$comment->owner_name}')"], "Reply"),
                    ),
                    emptyHTML(
                        Ctx::$user->can(IPBanPermission::VIEW_IP) ? emptyHTML(BR(), SHM_IP($comment->poster_ip, "Comment posted {$comment->posted}")) : null,
                        Ctx::$user->can(CommentPermission::DELETE_COMMENT) ? emptyHTML(" - ", $this->delete_link($comment->comment_id, $comment->image_id, $comment->owner_name, $tfe->stripped)) : null,
                    ),
                ),
                $userlink,
                ": ",
                $tfe->getFormattedHTML(),
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
        return DIV(
            ["class" => "comment comment_add"],
            SHM_SIMPLE_FORM(
                make_link("comment/add"),
                INPUT(["type" => "hidden", "name" => "image_id", "value" => $image_id]),
                INPUT(["type" => "hidden", "name" => "hash", "value" => CommentList::get_hash()]),
                TEXTAREA(["id" => "comment_on_$image_id", "name" => "comment", "rows" => 5, "cols" => 50]),
                Captcha::get_html(CommentPermission::SKIP_CAPTCHA),
                BR(),
                INPUT(["type" => "submit", "value" => "Post Comment"])
            ),
        );
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts containing a certain number of comments, or comments by a particular individual."),
            SHM_COMMAND_EXAMPLE("comments>0", "Returns posts with 1 or more comments"),
            P("Can use <, <=, >, >=, or =."),
            SHM_COMMAND_EXAMPLE("commented_by=username", "Returns posts that have been commented on by \"username\"."),
            SHM_COMMAND_EXAMPLE("comments_locked=yes", "Returns posts with locked comments"),
            SHM_COMMAND_EXAMPLE("comments_locked=no", "Returns posts with unlocked comments"),
            //SHM_COMMAND_EXAMPLE("commented_by_userno=123", "Returns posts that have been commented on by user 123."),
        );
    }

    public function get_comments_lock_editor_html(bool $comments_locked): HTMLElement
    {
        return SHM_POST_INFO(
            "Comments Locked",
            $comments_locked ? "Yes" : "No",
            Ctx::$user->can(CommentPermission::EDIT_COMMENT_LOCK) ? INPUT(["type" => "checkbox", "name" => "comments_locked", "checked" => $comments_locked]) : null
        );
    }
}
