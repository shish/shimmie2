<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomCommentListTheme extends CommentListTheme
{
    /**
     * @param array<array{0: Image, 1: Comment[]}> $images
     */
    public function display_comment_list(array $images, int $page_number, int $total_pages, bool $can_post): void
    {
        global $config, $page, $user;

        $page->disable_left();

        // parts for the whole page
        $prev = $page_number - 1;
        $next = $page_number + 1;

        $h_prev = ($page_number <= 1) ? "Prev" :
            "<a href='".make_link("comment/list/$prev")."'>Prev</a>";
        $h_index = "<a href='".make_link()."'>Index</a>";
        $h_next = ($page_number >= $total_pages) ? "Next" :
            "<a href='".make_link("comment/list/$next")."'>Next</a>";

        $nav = "$h_prev | $h_index | $h_next";

        $page->set_title("Comments");
        $page->set_heading("Comments");
        $page->add_block(new Block("Navigation", $nav, "left"));
        $this->display_paginator($page, "comment/list", null, $page_number, $total_pages);

        // parts for each image
        $position = 10;

        $comment_captcha = $config->get_bool('comment_captcha');
        $comment_limit = $config->get_int("comment_list_count", 10);

        foreach ($images as $pair) {
            $image = $pair[0];
            $comments = $pair[1];

            $thumb_html = $this->build_thumb_html($image);

            $s = "&nbsp;&nbsp;&nbsp;";
            $un = $image->get_owner()->name;
            $t = "";
            foreach ($image->get_tag_array() as $tag) {
                $t .= "<a href='".search_link([$tag])."'>".html_escape($tag)."</a> ";
            }
            $p = autodate($image->posted);

            $r = Extension::is_enabled(RatingsInfo::KEY) ? "<b>Rating</b> ".Ratings::rating_to_human($image['rating']) : "";
            $comment_html =   "<b>Date</b> $p $s <b>User</b> $un $s $r<br><b>Tags</b> $t<p>&nbsp;";

            $comment_count = count($comments);
            if ($comment_limit > 0 && $comment_count > $comment_limit) {
                //$hidden = $comment_count - $comment_limit;
                $comment_html .= "<p>showing $comment_limit of $comment_count comments</p>";
                $comments = array_slice($comments, negative_int($comment_limit));
            }
            foreach ($comments as $comment) {
                $comment_html .= $this->comment_to_html($comment);
            }
            if ($can_post) {
                if (!$user->is_anonymous()) {
                    $comment_html .= $this->build_postbox($image->id);
                } else {
                    if (!$comment_captcha) {
                        $comment_html .= $this->build_postbox($image->id);
                    } else {
                        $comment_html .= "<a href='".make_link("post/view/".$image->id)."'>Add Comment</a>";
                    }
                }
            }

            $html  = "
				<table><tr>
					<td style='width: 220px;'>$thumb_html</td>
					<td style='text-align: left;'>$comment_html</td>
				</tr></table>
			";


            $page->add_block(new Block("&nbsp;", $html, "main", $position++));
        }
    }

    public function display_recent_comments(array $comments): void
    {
        // no recent comments in this theme
    }


    protected function comment_to_html(Comment $comment, bool $trim = false): string
    {
        global $user;

        $tfe = send_event(new TextFormattingEvent($comment->comment));

        //$i_uid = $comment->owner_id;
        $h_name = html_escape($comment->owner_name);
        //$h_poster_ip = html_escape($comment->poster_ip);
        if ($trim) {
            $h_comment = truncate($tfe->stripped, 50);
        } else {
            $h_comment = $tfe->formatted;
        }
        $i_comment_id = $comment->comment_id;
        $i_image_id = $comment->image_id;
        $h_posted = autodate($comment->posted);

        $h_userlink = "<a class='username' href='".make_link("user/$h_name")."'>$h_name</a>";
        $h_del = "";
        if ($user->can(Permissions::DELETE_COMMENT)) {
            $h_del = " - " . $this->delete_link($i_comment_id, $i_image_id, $comment->owner_name, $tfe->stripped);
        }
        //$h_imagelink = $trim ? "<a href='".make_link("post/view/$i_image_id")."'>&gt;&gt;&gt;</a>\n" : "";
        if ($trim) {
            return "<p class='comment'>$h_userlink $h_del<br/>$h_posted<br/>$h_comment</p>";
        } else {
            return "
				<table class='comment'><tr>
					<td class='meta'>$h_userlink<br/>$h_posted$h_del</td>
					<td>$h_comment</td>
				</tr></table>
			";
        }
    }
}
