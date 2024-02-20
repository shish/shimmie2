<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\INPUT;

/**
 * @phpstan-type NoteHistory array{image_id:int,note_id:int,review_id:int,user_name:string,note:string,date:string}
 * @phpstan-type Note array{id:int,x1:int,y1:int,height:int,width:int,note:string}
 */
class NotesTheme extends Themelet
{
    public function note_button(int $image_id): HTMLElement
    {
        return SHM_SIMPLE_FORM("", INPUT(["type" => "button", "value" => "Add Note", "onclick" => "addNewNote()"]));
    }
    public function request_button(int $image_id): HTMLElement
    {
        return SHM_SIMPLE_FORM(
            "note/add_request",
            INPUT(["type" => "hidden", "name" => "image_id", "value" => $image_id]),
            INPUT(["type" => "submit", "value" => "Add Note Request"]),
        );
    }
    public function nuke_notes_button(int $image_id): HTMLElement
    {
        return SHM_SIMPLE_FORM(
            "note/nuke_notes",
            INPUT(["type" => "hidden", "name" => "image_id", "value" => $image_id]),
            INPUT(["type" => "submit", "value" => "Nuke Notes", "onclick" => "return confirm_action('Are you sure?')"]),
        );
    }
    public function nuke_requests_button(int $image_id): HTMLElement
    {
        return SHM_SIMPLE_FORM(
            "note/nuke_requests",
            INPUT(["type" => "hidden", "name" => "image_id", "value" => $image_id]),
            INPUT(["type" => "submit", "value" => "Nuke Requests", "onclick" => "return confirm_action('Are you sure?')"]),
        );
    }

    // check action POST on form
    /**
     * @param Note[] $recovered_notes
     */
    public function display_note_system(Page $page, int $image_id, array $recovered_notes, bool $adminOptions, bool $editOptions): void
    {
        $to_json = [];
        foreach ($recovered_notes as $note) {
            $to_json[] = [
                'image_id' => $image_id,
                'x1'      => $note["x1"],
                'y1'      => $note["y1"],
                'height'  => $note["height"],
                'width'   => $note["width"],
                'note'    => $note["note"],
                'note_id' => $note["id"],
            ];
        }
        $page->add_html_header("<script type='text/javascript'>
        window.notes = ".\Safe\json_encode($to_json).";
        window.notes_image_id = $image_id;
        window.notes_admin = ".($adminOptions ? "true" : "false").";
        window.notes_edit = ".($editOptions ? "true" : "false").";
        </script>");
    }

    /**
     * @param array<Image> $images
     */
    public function display_note_list(array $images, int $pageNumber, int $totalPages): void
    {
        global $page;
        $pool_images = '';
        foreach ($images as $image) {
            $thumb_html = $this->build_thumb_html($image);

            $pool_images .= '<span class="thumb">'.
                            '    <a href="$image_link">'.$thumb_html.'</a>'.
                            '</span>';
        }
        $this->display_paginator($page, "note/list", null, $pageNumber, $totalPages);

        $page->set_title("Notes");
        $page->set_heading("Notes");
        $page->add_block(new Block("Notes", $pool_images, "main", 20));
    }

    /**
     * @param array<Image> $images
     */
    public function display_note_requests(array $images, int $pageNumber, int $totalPages): void
    {
        global $page;

        $pool_images = '';
        foreach ($images as $image) {
            $thumb_html = $this->build_thumb_html($image);
            $pool_images .= '<span class="thumb">'.
                            '    <a href="$image_link">'.$thumb_html.'</a>'.
                            '</span>';
        }
        $this->display_paginator($page, "requests/list", null, $pageNumber, $totalPages);

        $page->set_title("Note Requests");
        $page->set_heading("Note Requests");
        $page->add_block(new Block("Note Requests", $pool_images, "main", 20));
    }

    /**
     * @param NoteHistory[] $histories
     */
    private function get_history(array $histories): string
    {
        global $user;

        $html = "<table id='poolsList' class='zebra'>".
                "<thead><tr>".
                "<th>Post</th>".
                "<th>Note</th>".
                "<th>Body</th>".
                "<th>Updater</th>".
                "<th>Date</th>";

        if (!$user->is_anonymous()) {
            $html .= "<th>Action</th>";
        }

        $html .= "</tr></thead>".
                 "<tbody>";

        foreach ($histories as $history) {
            $image_link = "<a href='".make_link("post/view/".$history['image_id'])."'>".$history['image_id']."</a>";
            $history_link = "<a href='".make_link("note/history/".$history['note_id'])."'>".$history['note_id'].".".$history['review_id']."</a>";
            $user_link = "<a href='".make_link("user/".$history['user_name'])."'>".$history['user_name']."</a>";
            $revert_link = "<a href='".make_link("note/revert/".$history['note_id']."/".$history['review_id'])."'>Revert</a>";

            $html .= "<tr>".
                     "<td>".$image_link."</td>".
                     "<td>".$history_link."</td>".
                     "<td style='text-align:left;'>".$history['note']."</td>".
                     "<td>".$user_link."</td>".
                     "<td>".autodate($history['date'])."</td>";

            if (!$user->is_anonymous()) {
                $html .= "<td>".$revert_link."</td>";
            }
        }

        $html .= "</tr></tbody></table>";

        return $html;
    }

    /**
     * @param NoteHistory[] $histories
     */
    public function display_histories(array $histories, int $pageNumber, int $totalPages): void
    {
        global $page;

        $html = $this->get_history($histories);

        $page->set_title("Note Updates");
        $page->set_heading("Note Updates");
        $page->add_block(new Block("Note Updates", $html, "main", 10));

        $this->display_paginator($page, "note/updated", null, $pageNumber, $totalPages);
    }

    /**
     * @param NoteHistory[] $histories
     */
    public function display_history(array $histories, int $pageNumber, int $totalPages): void
    {
        global $page;

        $html = $this->get_history($histories);

        $page->set_title("Note History");
        $page->set_heading("Note History");
        $page->add_block(new Block("Note History", $html, "main", 10));

        $this->display_paginator($page, "note/updated", null, $pageNumber, $totalPages);
    }

    public function get_help_html(): string
    {
        return '<p>Search for posts with notes.</p>
        <div class="command_example">
        <pre>note=noted</pre>
        <p>Returns posts with a note matching "noted".</p>
        </div>
        <div class="command_example">
        <pre>notes>0</pre>
        <p>Returns posts with 1 or more notes.</p>
        </div>
        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =.</p>
        <div class="command_example">
        <pre>notes_by=username</pre>
        <p>Returns posts with note(s) by "username".</p>
        </div>
        <div class="command_example">
        <pre>notes_by_user_id=123</pre>
        <p>Returns posts with note(s) by user 123.</p>
        </div>
        ';
    }
}
