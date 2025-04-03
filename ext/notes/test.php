<?php

declare(strict_types=1);

namespace Shimmie2;

final class NotesTest extends ShimmiePHPUnitTestCase
{
    public function testList(): void
    {
        $page = self::get_page("note/list");
        self::assertEquals(200, $page->code);
    }

    public function testRequests(): void
    {
        $page = self::get_page("note/requests");
        self::assertEquals(200, $page->code);
    }

    public function testUpdated(): void
    {
        $page = self::get_page("note/updated");
        self::assertEquals(200, $page->code);
    }

    // self::get_page("note/history/$note_id");
    // self::get_page("note_history/$image_id");
    // self::get_page("note/revert/{noteID}/{reviewID}");
    // self::get_page("note/add_request");
    // self::get_page("note/nuke_requests");
    // self::get_page("note/create_note");
    // self::get_page("note/update_note");
    // self::get_page("note/delete_note");
    // self::get_page("note/nuke_notes");
}
