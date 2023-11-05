<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TR, TH, TD, emptyHTML, DIV, INPUT};

class RelationshipsTheme extends Themelet
{
    public function relationship_info(Image $image)
    {
        global $page, $database;

        if ($image->parent_id !== null) {
            $a = "<a href='".make_link("post/view/".$image->parent_id)."'>parent post</a>";
            $page->add_block(new Block(null, "This post belongs to a $a.", "main", 5, "ImageHasParent"));
        }

        if (bool_escape($image->has_children)) {
            $ids = $database->get_col("SELECT id FROM images WHERE parent_id = :iid", ["iid"=>$image->id]);

            $html = "This post has <a href='".search_link(['parent='.$image->id])."'>".(count($ids) > 1 ? "child posts" : "a child post")."</a>";
            $html .= " (post ";
            foreach ($ids as $id) {
                $html .= "#<a href='".make_link('post/view/'.$id)."'>{$id}</a>, ";
            }
            $html = rtrim($html, ", ").").";

            $page->add_block(new Block(null, $html, "main", 6, "ImageHasChildren"));
        }
    }

    public function get_parent_editor_html(Image $image): HTMLElement
    {
        global $user;

        return SHM_POST_INFO(
            "Parent",
            !$user->is_anonymous(),
            strval($image->parent_id) ?: "None",
            INPUT(["type"=>"number", "name"=>"tag_edit__parent", "value"=>$image->parent_id])
        );
    }


    public function get_help_html(): string
    {
        return '<p>Search for posts that have parent/child relationships.</p>
        <div class="command_example">
        <pre>parent=any</pre>
        <p>Returns posts that have a parent.</p>
        </div>
        <div class="command_example">
        <pre>parent=none</pre>
        <p>Returns posts that have no parent.</p>
        </div>
        <div class="command_example">
        <pre>parent=123</pre>
        <p>Returns posts that have image 123 set as parent.</p>
        </div>
        <div class="command_example">
        <pre>child=any</pre>
        <p>Returns posts that have at least 1 child.</p>
        </div>
        <div class="command_example">
        <pre>child=none</pre>
        <p>Returns posts that have no children.</p>
        </div>
        ';
    }
}
