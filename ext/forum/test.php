<?php

declare(strict_types=1);

namespace Shimmie2;

final class ForumTest extends ShimmiePHPUnitTestCase
{
    public function testCreateAndView(): void
    {
        self::log_in_as_admin();

        // list
        self::get_page("forum/index");

        // create thread, with response
        self::get_page("forum/new");
        self::post_page("forum/create", ["title" => "My new thread", "message" => "My new message"]);
        $thread_id = Ctx::$database->get_one("SELECT id FROM forum_threads ORDER BY id DESC LIMIT 1");

        // create response
        self::post_page("forum/answer", ["threadID" => "$thread_id", "message" => "My new response"]);
        self::get_page("forum/view/$thread_id");
        $post_id = Ctx::$database->get_one("SELECT id FROM forum_posts ORDER BY id DESC LIMIT 1");

        // list
        self::get_page("forum/index");
        self::assert_text("My new thread");

        // delete response
        self::post_page("forum/delete/$thread_id/$post_id");

        // delete thread
        self::post_page("forum/nuke/$thread_id");

        // list
        self::get_page("forum/index");
        self::assert_no_text("My new thread");
    }
}
