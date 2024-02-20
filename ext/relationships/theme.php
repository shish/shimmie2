<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TR, TH, TD, emptyHTML, DIV, INPUT};

class RelationshipsTheme extends Themelet
{
    public function relationship_info(Image $image): void
    {
        global $page, $database;

        $parent = Search::get_images([$image['parent_id']]);
        if (!empty($parent)) {
            $parent_id = $image['parent_id'];
            $a = "<a href='".make_link("post/view/".$parent_id)."'>#$parent_id</a>";
            $parent_summary_html = "<span>This post belongs to a parent post ($a)";
            $parent_thumb_html = "<div class='shm-relationships-parent-thumbs'><div class='shm-parent-thumbs'>" . $this->get_parent_thumbnail_html($image) . "</div>";
            if (Relationships::has_siblings($image->id)) {
                $visible_siblings = Relationships::get_siblings($image->id);
                if (!empty($visible_siblings)) {
                    $parent_summary_html .= " and has " .count($visible_siblings) . (count($visible_siblings) > 1 ? " siblings" : " sibling");
                    $parent_summary_html .= " (";
                    foreach ($visible_siblings as $sibling) {
                        $parent_summary_html .= "<a href='" . make_link('post/view/'.$sibling->id) . "'>#$sibling->id</a>" . (count($visible_siblings) > 1 ? ", " : "");
                    }
                    $parent_summary_html = trim($parent_summary_html, ', ');
                    $parent_summary_html .= ")";
                    $parent_thumb_html .= "<div class='shm-sibling-thumbs'>" . $this->get_sibling_thumbnail_html($image) . "</div>";
                }
            }
            $parent_summary_html .= ".</span>";
            $parent_summary_html .= "<a href='#' id='relationships-parent-toggle' class='shm-relationships-parent-toggle'>« hide</a>";
            $parent_thumb_html .= "</div>";
            $html = $parent_summary_html . $parent_thumb_html;
            $page->add_block(new Block(null, $html, "main", 5, "PostRelationshipsParent"));
        }

        if (bool_escape($image['has_children'])) {
            $visible_children = Relationships::get_children($image->id);
            if (!empty($visible_children)) {
                $child_summary_html = "<span>This post has <a href='".make_link('post/list/parent='.$image->id.'/1')."'>".(count($visible_children) > 1 ? "child posts" : "a child post")."</a>";
                $child_summary_html .= " (post ";
                $child_thumb_html = "<div class='shm-relationships-child-thumbs'><div class='shm-child-thumbs'>";
                foreach ($visible_children as $child) {
                    $child_summary_html .= "<a href='".make_link('post/view/'.$child->id)."'>#{$child->id}</a>, ";
                    $child_thumb_html .= $this->get_child_thumbnail_html($child);
                }
                $child_summary_html = rtrim($child_summary_html, ", ").").";
                $child_summary_html .= "</span><a href='#' id='relationships-child-toggle' class='shm-relationships-child-toggle'>« hide</a>";
                $child_thumb_html .= "</div></div>";
                $html = $child_summary_html . $child_thumb_html;
                $page->add_block(new Block(null, $html, "main", 5, "PostRelationshipsChildren"));
            }
        }
    }

    public function get_parent_editor_html(Image $image): HTMLElement
    {
        global $user;

        return SHM_POST_INFO(
            "Parent",
            strval($image['parent_id']) ?: "None",
            !$user->is_anonymous() ? INPUT(["type" => "number", "name" => "parent", "value" => $image['parent_id']]) : null
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

    private function get_parent_thumbnail_html(Image $image): HTMLElement
    {
        $parent_id = $image['parent_id'];
        $parent_image = Image::by_id_ex($parent_id);

        return $this->build_thumb_html($parent_image);
    }

    private function get_child_thumbnail_html(Image $image): HTMLElement
    {
        return $this->build_thumb_html($image);
    }

    private function get_sibling_thumbnail_html(Image $image): string
    {
        $siblings = Relationships::get_siblings($image->id);
        $html = "";

        foreach ($siblings as $sibling) {
            $html .= $this->build_thumb_html($sibling);
        }

        return $html;
    }
}
