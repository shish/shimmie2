<?php

declare(strict_types=1);

namespace Shimmie2;

class CustomCommentListTheme extends CommentListTheme
{
    public int $inner_id = 0;

    /**
     * @param array<array{0: Image, 1: Comment[]}> $images
     */
    public function display_comment_list(array $images, int $page_number, int $total_pages, bool $can_post): void
    {
        global $config, $page;

        //$prev = $page_number - 1;
        //$next = $page_number + 1;

        $page_title = $config->get_string(SetupConfig::TITLE);
        $page->set_title($page_title);
        $page->set_heading($page_title);
        $page->disable_left();
        $page->add_block(new Block(null, $this->build_upload_box(), "main", 0));
        $page->add_block(new Block(null, "<hr>", "main", 80));
        $this->display_paginator($page, "comment/list", null, $page_number, $total_pages);

        // parts for each image
        $position = 10;
        foreach ($images as $pair) {
            $image = $pair[0];
            $comments = $pair[1];

            $h_filename = html_escape($image->filename);
            $h_filesize = to_shorthand_int($image->filesize);
            $w = $image->width;
            $h = $image->height;

            $comment_html = "";
            $comment_id = 0;
            foreach ($comments as $comment) {
                $this->inner_id = $comment_id++;
                $comment_html .= $this->comment_to_html($comment, false);
            }

            $html  = "<p style='clear:both'>&nbsp;</p><hr >";
            $html .= "File: <a href=\"".make_link("post/view/{$image->id}")."\">$h_filename</a> - ($h_filesize, {$w}x{$h}) - ";
            $html .= html_escape($image->get_tag_list());
            $html .= "<div style='text-align: left'>";
            $html .=   "<div style='float: left;'>" . $this->build_thumb_html($image) . "</div>";
            $html .=   "<div class='commentset'>$comment_html</div>";
            $html .= "</div>";

            $page->add_block(new Block(null, $html, "main", $position++));
        }
    }

    public function display_recent_comments(array $comments): void
    {
        // sidebar fails in this theme
    }

    public function build_upload_box(): string
    {
        return "[[ insert upload-and-comment extension here ]]";
    }


    protected function comment_to_html(Comment $comment, bool $trim = false): string
    {
        $inner_id = $this->inner_id; // because custom themes can't add params, because PHP
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

        $h_userlink = "<a href='".make_link("user/$h_name")."'>$h_name</a>";
        $h_date = $comment->posted;
        $h_del = "";
        if ($user->can(Permissions::DELETE_COMMENT)) {
            $h_del = " - " . $this->delete_link($i_comment_id, $i_image_id, $comment->owner_name, $tfe->stripped);
        }
        $h_reply = "[<a href='".make_link("post/view/$i_image_id")."'>Reply</a>]";

        if ($inner_id == 0) {
            return "<div class='comment' style='margin-top: 8px;'>$h_userlink$h_del $h_date No.$i_comment_id $h_reply<p>$h_comment</p></div>";
        } else {
            return "<table><tr><td nowrap class='doubledash'>&gt;&gt;</td><td>".
                "<div class='reply'>$h_userlink$h_del $h_date No.$i_comment_id $h_reply<p>$h_comment</p></div>" .
                "</td></tr></table>";
        }
    }
}
