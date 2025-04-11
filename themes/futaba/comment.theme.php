<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, DIV, HR, P, TABLE, TD, TR, emptyHTML};

use MicroHTML\HTMLElement;

class FutabaCommentListTheme extends CommentListTheme
{
    public int $inner_id = 0;
    public bool $post_page = true;

    /**
     * @param array<array{0: Image, 1: Comment[]}> $images
     */
    public function display_comment_list(array $images, int $page_number, int $total_pages, bool $can_post): void
    {
        Ctx::$page->set_title(Ctx::$config->get(SetupConfig::TITLE));
        Ctx::$page->set_layout("no-left");
        Ctx::$page->add_block(new Block(null, $this->build_upload_box(), "main", 0));
        Ctx::$page->add_block(new Block(null, HR(), "main", 80));
        $this->display_paginator("comment/list", null, $page_number, $total_pages);
        $this->post_page = false;

        // parts for each image
        $position = 10;
        foreach ($images as $pair) {
            $image = $pair[0];
            $comments = $pair[1];

            $h_filesize = to_shorthand_int($image->filesize);
            $w = $image->width;
            $h = $image->height;

            $comment_html = [];
            $comment_id = 0;
            foreach ($comments as $comment) {
                $this->inner_id = $comment_id++;
                $comment_html[] = $this->comment_to_html($comment, false);
            }

            $html = emptyHTML(
                P(["style" => "clear:both"], " "),
                HR(),
                "File: ",
                A(["href" => make_link("post/view/{$image->id}")], $image->filename),
                " - ($h_filesize, {$w}x{$h}) - ",
                $image->get_tag_list(),
                DIV(
                    ["style" => "text-align: left"],
                    DIV(["style" => "float: left;"], $this->build_thumb($image)),
                    DIV(["class" => "commentset"], ...$comment_html)
                )
            );

            Ctx::$page->add_block(new Block(null, $html, "main", $position++));
        }
    }

    public function display_recent_comments(array $comments): void
    {
        $this->post_page = false;
        parent::display_recent_comments($comments);
    }

    public function build_upload_box(): HTMLElement
    {
        return emptyHTML("[[ insert upload-and-comment extension here ]]");
    }

    protected function comment_to_html(Comment $comment, bool $trim = false): HTMLElement
    {
        // because custom themes can't add params, because PHP
        $inner_id = $this->inner_id;

        $tfe = send_event(new TextFormattingEvent($comment->comment));

        //$i_uid = $comment->owner_id;
        $h_name = html_escape($comment->owner_name);
        //$h_poster_ip = html_escape($comment->poster_ip);
        if ($trim) {
            $h_comment = truncate($tfe->stripped, 50);
        } else {
            $h_comment = $tfe->formatted;
        }
        $h_comment = \Safe\preg_replace("/(^|>)(&gt;[^<\n]*)(<|\n|$)/", '${1}<span class=\'greentext\'>${2}</span>${3}', $h_comment);
        // handles discrepency in comment page and homepage
        $h_comment = str_replace("<br>", "", $h_comment);
        $h_comment = str_replace("\n", "<br>", $h_comment);
        $i_comment_id = $comment->comment_id;
        $i_image_id = $comment->image_id;

        $h_userlink = "<a class='username' href='".make_link("user/$h_name")."'>$h_name</a>";
        $h_date = $comment->posted;
        $h_del = "";
        if (Ctx::$user->can(CommentPermission::DELETE_COMMENT)) {
            $comment_preview = substr(html_unescape($tfe->stripped), 0, 50);
            $j_delete_confirm_message = json_encode("Delete comment by {$comment->owner_name}:\n$comment_preview");
            $h_delete_script = html_escape("return confirm($j_delete_confirm_message);");
            $h_delete_link = make_link("comment/delete/$i_comment_id/$i_image_id");
            $h_del = " - [<a onclick='$h_delete_script' href='$h_delete_link'>Delete</a>]";
        }
        if ($this->post_page) {
            $h_reply = "[<a href='javascript: replyTo($i_image_id, $i_comment_id, \"$h_name\")'>Reply</a>]";
        } else {
            $h_reply = "[<a href='".make_link("post/view/$i_image_id")."'>Reply</a>]";
        }

        if ($inner_id === 0) {
            return DIV(
                ["class" => "comment", "style" => "margin-top: 8px;"],
                $h_userlink,
                $h_del,
                $h_date,
                "No.$i_comment_id",
                $h_reply,
                P($h_comment)
            );
        } else {
            return TABLE(TR(
                TD(["nowrap" => true, "class" => "doubledash"], ">>"),
                TD(DIV(["class" => "reply"], $h_userlink, $h_del, $h_date, "No.$i_comment_id", P($h_comment)))
            ));
        }
    }
}
