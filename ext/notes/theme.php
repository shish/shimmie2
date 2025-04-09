<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, P, TABLE, TBODY, TD, TH, THEAD, TR, emptyHTML, joinHTML};
use function MicroHTML\{FORM, INPUT, SCRIPT};

use MicroHTML\HTMLElement;

/**
 * @phpstan-type NoteHistory array{image_id:int,note_id:int,review_id:int,user_name:string,note:string,date:string}
 * @phpstan-type Note array{id:int,x1:int,y1:int,height:int,width:int,note:string}
 */
class NotesTheme extends Themelet
{
    public function note_button(int $image_id): HTMLElement
    {
        return FORM(INPUT(["type" => "button", "value" => "Add Note", "onclick" => "addNewNote()"]));
    }
    public function request_button(int $image_id): HTMLElement
    {
        return SHM_SIMPLE_FORM(
            make_link("note/add_request"),
            INPUT(["type" => "hidden", "name" => "image_id", "value" => $image_id]),
            INPUT(["type" => "submit", "value" => "Add Note Request"]),
        );
    }
    public function nuke_notes_button(int $image_id): HTMLElement
    {
        return SHM_SIMPLE_FORM(
            make_link("note/nuke_notes"),
            INPUT(["type" => "hidden", "name" => "image_id", "value" => $image_id]),
            INPUT(["type" => "submit", "value" => "Nuke Notes", "onclick" => "return confirm_action('Are you sure?')"]),
        );
    }
    public function nuke_requests_button(int $image_id): HTMLElement
    {
        return SHM_SIMPLE_FORM(
            make_link("note/nuke_requests"),
            INPUT(["type" => "hidden", "name" => "image_id", "value" => $image_id]),
            INPUT(["type" => "submit", "value" => "Nuke Requests", "onclick" => "return confirm_action('Are you sure?')"]),
        );
    }

    // check action POST on form
    /**
     * @param Note[] $recovered_notes
     */
    public function display_note_system(int $image_id, array $recovered_notes, bool $adminOptions, bool $editOptions): void
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
        Ctx::$page->add_html_header(SCRIPT(
            ["type" => "text/javascript"],
            \MicroHTML\rawHTML("
            window.notes = ".\Safe\json_encode($to_json).";
            window.notes_image_id = $image_id;
            window.notes_admin = ".($adminOptions ? "true" : "false").";
            window.notes_edit = ".($editOptions ? "true" : "false").";
            ")
        ));
    }

    /**
     * @param array<Image> $images
     */
    public function display_note_list(array $images, int $pageNumber, int $totalPages): void
    {
        $thumbs = array_map(fn ($image) => $this->build_thumb($image), $images);

        $page = Ctx::$page;
        $page->set_title("Notes");
        $page->add_block(new Block("Notes", joinHTML(" ", $thumbs), "main", 20));
        $this->display_paginator("note/list", null, $pageNumber, $totalPages);
    }

    /**
     * @param array<Image> $images
     */
    public function display_note_requests(array $images, int $pageNumber, int $totalPages): void
    {
        $thumbs = array_map(fn ($image) => $this->build_thumb($image), $images);

        $page = Ctx::$page;
        $page->set_title("Note Requests");
        $page->add_block(new Block("Note Requests", joinHTML(" ", $thumbs), "main", 20));
        $this->display_paginator("requests/list", null, $pageNumber, $totalPages);
    }

    /**
     * @param NoteHistory[] $histories
     */
    private function get_history(array $histories): HTMLElement
    {
        $tbody = TBODY();
        foreach ($histories as $history) {
            $tbody->appendChild(TR(
                TD(A(["href" => make_link("post/view/".$history['image_id'])], $history['image_id'])),
                TD(A(["href" => make_link("note/view/".$history['note_id'])], $history['note_id'].".".$history['review_id'])),
                TD(["style" => "text-align:left;"], $history['note']),
                TD(A(["href" => make_link("user/".$history['user_name'])], $history['user_name'])),
                TD(SHM_DATE($history['date'])),
                TD(Ctx::$user->can(NotesPermission::EDIT) ? TD(A(["href" => make_link("note/revert/".$history['note_id']."/".$history['review_id'])], "Revert")) : null),
            ));
        }

        return TABLE(
            ["class" => "zebra"],
            THEAD(
                TR(
                    TH("Post"),
                    TH("Note"),
                    TH("Body"),
                    TH("Updater"),
                    TH("Date"),
                    Ctx::$user->can(NotesPermission::EDIT) ? TH("Action") : null
                )
            ),
            $tbody
        );
    }

    /**
     * @param NoteHistory[] $histories
     */
    public function display_histories(array $histories, int $pageNumber, int $totalPages): void
    {
        $page = Ctx::$page;
        $page->set_title("Note Updates");
        $page->add_block(new Block("Note Updates", $this->get_history($histories), "main", 10));
        $this->display_paginator("note/updated", null, $pageNumber, $totalPages);
    }

    /**
     * @param NoteHistory[] $histories
     */
    public function display_history(array $histories, int $pageNumber, int $totalPages): void
    {
        $page = Ctx::$page;
        $page->set_title("Note History");
        $page->add_block(new Block("Note History", $this->get_history($histories), "main", 10));
        $this->display_paginator("note/updated", null, $pageNumber, $totalPages);
    }

    /**
     * @param NoteHistory[] $histories
     */
    public function display_image_history(array $histories, int $imageID, int $pageNumber, int $totalPages): void
    {
        $page = Ctx::$page;
        $page->set_title("Note History #$imageID");
        $page->set_heading("Note History #$imageID");
        $page->add_block(new Block("Note History #$imageID", $this->get_history($histories), "main", 10));

        $this->display_paginator("note_history/$imageID", null, $pageNumber, $totalPages);
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts with notes."),
            SHM_COMMAND_EXAMPLE("note=noted", "Returns posts with a note matching 'noted'."),
            SHM_COMMAND_EXAMPLE("notes>0", "Returns posts with 1 or more notes."),
            P("Can use <, <=, >, >=, or =."),
            SHM_COMMAND_EXAMPLE("notes_by=username", "Returns posts with note(s) by 'username'."),
        );
    }
}
