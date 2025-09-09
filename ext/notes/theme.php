<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, DIV, P, TABLE, TBODY, TD, TH, THEAD, TR, emptyHTML, joinHTML};
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
        return FORM(INPUT(["type" => "button", "value" => "Add Note", "onclick" => "Notes.addNewNote()"]));
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
            INPUT(["type" => "submit", "value" => "Nuke Notes", "onclick" => "return confirm('Are you sure?')"]),
        );
    }
    public function nuke_requests_button(int $image_id): HTMLElement
    {
        return SHM_SIMPLE_FORM(
            make_link("note/nuke_requests"),
            INPUT(["type" => "hidden", "name" => "image_id", "value" => $image_id]),
            INPUT(["type" => "submit", "value" => "Nuke Requests", "onclick" => "return confirm('Are you sure?')"]),
        );
    }

    /**
     * @param Note[] $recovered_notes
     */
    public function display_note_system(int $image_id, array $recovered_notes, bool $adminOptions, bool $editOptions): void
    {
        $notes = [];
        foreach ($recovered_notes as $note) {
            $notes[] = [
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
            window.notes = ".\Safe\json_encode($notes).";
            window.notes_image_id = $image_id;
            window.notes_admin = ".\Safe\json_encode($adminOptions).";
            window.notes_edit = ".\Safe\json_encode($editOptions).";
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
     * @param NoteHistory[] $history
     */
    protected function history_list(array $history, bool $allowRevert): HTMLElement
    {
        $history_list = [];
        foreach ($history as $n => $fields) {
            $history_list[] = $this->history_entry($fields, $allowRevert && $n !== 0);
        }

        return DIV(
            TABLE(
                ["class" => "zebra", "style" => "text-align: left"],
                THEAD(
                    TR(
                        TH("Post"),
                        TH("Note"),
                        TH("Body"),
                        TH("Updater"),
                        TH("Date"),
                        Ctx::$user->can(NotesPermission::EDIT) && $allowRevert ? TH("Action") : null
                    )
                ),
                TBODY(
                    ...$history_list
                ),
            )
        );
    }

    /**
     * @param NoteHistory $fields
     */
    protected function history_entry(array $fields, bool $revertable): HTMLElement
    {
        $image_id = $fields['image_id'];
        $note_id = $fields['note_id'];
        $review_id = $fields['review_id'];
        $note_text = $fields['note'];
        $name = $fields['user_name'];
        $date_set = SHM_DATE($fields['date']);
        $setter = A(["href" => make_link("user/" . url_escape($name))], $name);

        return TR(
            TD(A(["href" => make_link("post/view/$image_id")], $image_id)),
            TD(A(["href" => make_link("note/history/$note_id")], "$note_id.$review_id")),
            TD($note_text),
            TD($setter),
            TD($date_set),
            TD(
                Ctx::$user->can(NotesPermission::EDIT) && $revertable ?
                SHM_FORM(
                    action: make_link("note/revert/$note_id/$review_id"),
                    children: [SHM_SUBMIT("Revert To")]
                ) : null
            )
        );
    }

    /**
     * @param NoteHistory[] $history
     */
    public function display_histories(array $history, int $pageNumber, int $totalPages): void
    {
        $page = Ctx::$page;
        $page->set_title("Note Updates");
        $page->add_block(new Block("Note Updates", $this->history_list($history, false), "main", 10));
        $this->display_paginator("note/updated", null, $pageNumber, $totalPages);
    }

    /**
     * @param NoteHistory[] $history
     */
    public function display_history(array $history, int $pageNumber, int $totalPages): void
    {
        $page = Ctx::$page;
        $page->set_title("Note History");
        $page->add_block(new Block("Note History", $this->history_list($history, true), "main", 10));
        $this->display_paginator("note/updated", null, $pageNumber, $totalPages);
    }

    /**
     * @param NoteHistory[] $history
     */
    public function display_image_history(array $history, int $imageID, int $pageNumber, int $totalPages): void
    {
        $page = Ctx::$page;
        $page->set_title("Note History #$imageID");
        $page->set_heading("Note History #$imageID");
        $page->add_block(new Block("Note History #$imageID", $this->history_list($history, true), "main", 10));
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
