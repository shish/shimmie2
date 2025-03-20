<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\A;
use function MicroHTML\B;
use function MicroHTML\BR;
use function MicroHTML\P;
use function MicroHTML\SPAN;
use function MicroHTML\TABLE;
use function MicroHTML\TD;
use function MicroHTML\TR;
use function MicroHTML\emptyHTML;
use function MicroHTML\joinHTML;

class Danbooru2CommentListTheme extends CommentListTheme
{
    /**
     * @param array<array{0: Image, 1: Comment[]}> $images
     */
    public function display_comment_list(array $images, int $page_number, int $total_pages, bool $can_post): void
    {
        global $config, $page, $user;

        $page->set_layout("no-left");

        $page->set_title("Comments");
        $this->display_navigation([
            ($page_number <= 1) ? null : make_link('comment/list/'.($page_number - 1)),
            make_link(),
            ($page_number >= $total_pages) ? null : make_link('comment/list/'.($page_number + 1))
        ]);
        $this->display_paginator("comment/list", null, $page_number, $total_pages);

        // parts for each image
        $position = 10;

        $comment_captcha = $config->get_bool(CommentConfig::CAPTCHA);
        $comment_limit = $config->get_int(CommentConfig::LIST_COUNT, 10);

        foreach ($images as $pair) {
            $image = $pair[0];
            $comments = $pair[1];

            $tags = [];
            foreach ($image->get_tag_array() as $tag) {
                $tags[] = A(["href" => search_link([$tag])], $tag);
            }

            $comment_html = SPAN(
                ["class" => "comment-info"],
                SPAN(
                    B("Date"),
                    SHM_DATE($image->posted),
                ),
                SPAN(
                    B("Uploader"),
                    $image->get_owner()->name,
                ),
                RatingsInfo::is_enabled()
                    ? SPAN(B("Rating"), Ratings::rating_to_human($image['rating']))
                    : null,
                BR(),
                SPAN(
                    B("Tags"),
                    joinHTML(" ", $tags),
                ),
                P(),
            );

            $comment_count = count($comments);
            if ($comment_limit > 0 && $comment_count > $comment_limit) {
                $comment_html->appendChild(P("showing $comment_limit of $comment_count comments"));
                $comments = array_slice($comments, negative_int($comment_limit));
            }
            foreach ($comments as $comment) {
                $comment_html->appendChild($this->comment_to_html($comment));
            }
            if ($can_post) {
                if (!$user->is_anonymous()) {
                    $comment_html->appendChild($this->build_postbox($image->id));
                } else {
                    if (!$comment_captcha) {
                        $comment_html->appendChild($this->build_postbox($image->id));
                    } else {
                        $comment_html->appendChild(A(["href" => make_link("post/view/".$image->id)], "Add Comment"));
                    }
                }
            }

            $html = TABLE(
                TR(
                    TD(["style" => "width: 220px;"], $this->build_thumb($image)),
                    TD(["style" => "text-align: left;"], $comment_html)
                )
            );

            $page->add_block(new Block(null, $html, "main", $position++));
        }
    }

    public function display_recent_comments(array $comments): void
    {
        // no recent comments in this theme
    }

    protected function comment_to_html(Comment $comment, bool $trim = false): HTMLElement
    {
        global $user;

        $tfe = send_event(new TextFormattingEvent($comment->comment));

        if ($trim) {
            $h_comment = truncate($tfe->stripped, 50);
        } else {
            $h_comment = $tfe->getFormattedHTML();
        }
        $h_posted = SHM_DATE($comment->posted);

        $h_userlink = A(["class" => "username", "href" => make_link("user/{$comment->owner_name}")], $comment->owner_name);
        $h_del = null;
        if ($user->can(CommentPermission::DELETE_COMMENT)) {
            $h_del = emptyHTML(" - ", $this->delete_link($comment->comment_id, $comment->image_id, $comment->owner_name, $tfe->stripped));
        }
        if ($trim) {
            return P(
                ["class" => "comment"],
                $h_userlink,
                $h_del,
                BR(),
                $h_posted,
                BR(),
                $h_comment
            );
        } else {
            return TABLE(
                ["class" => "comment"],
                TR(
                    TD(["class" => "meta"], $h_userlink, BR(), $h_posted, $h_del),
                    TD($h_comment)
                )
            );
        }
    }
}
