<?php

declare(strict_types=1);

namespace Shimmie2;

final class CommentListTest extends ShimmiePHPUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Ctx::$config->set(CommentConfig::LIMIT, 100);
        self::log_out();
    }

    public function testCommentsPage(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");

        # a good comment
        send_event(new CommentPostingEvent($image_id, Ctx::$user, "Test Comment ASDFASDF"));
        self::get_page("post/view/$image_id");
        self::assert_text("ASDFASDF");

        # dupe
        try {
            send_event(new CommentPostingEvent($image_id, Ctx::$user, "Test Comment ASDFASDF"));
        } catch (CommentPostingException $e) {
            self::assertStringContainsString("try and be more original", $e->getMessage());
        }

        # empty comment
        try {
            send_event(new CommentPostingEvent($image_id, Ctx::$user, ""));
        } catch (CommentPostingException $e) {
            self::assertStringContainsString("Comments need text", $e->getMessage());
        }

        # whitespace is still empty...
        try {
            send_event(new CommentPostingEvent($image_id, Ctx::$user, " \t\r\n"));
        } catch (CommentPostingException $e) {
            self::assertStringContainsString("Comments need text", $e->getMessage());
        }

        # repetitive (aka. gzip gives >= 10x improvement)
        try {
            send_event(new CommentPostingEvent($image_id, Ctx::$user, str_repeat("U", 5000)));
        } catch (CommentPostingException $e) {
            self::assertStringContainsString("Comment too repetitive", $e->getMessage());
        }

        # test UTF8
        send_event(new CommentPostingEvent($image_id, Ctx::$user, "Test Comment むちむち"));
        self::get_page("post/view/$image_id");
        self::assert_text("むちむち");

        # test that search by comment metadata works
        //		self::get_page("post/list/commented_by=test/1");
        //		self::assert_title("Image $image_id: pbx");
        //		self::get_page("post/list/comments=2/1");
        //		self::assert_title("Image $image_id: pbx");

        self::log_out();

        self::get_page('comment/list');
        self::assert_title('Comments');
        self::assert_text('ASDFASDF');

        self::get_page('comment/list/2');
        self::assert_title('Comments');

        self::log_in_as_admin();
        $this->delete_image($image_id);
        self::log_out();

        self::get_page('comment/list');
        self::assert_title('Comments');
        self::assert_no_text('ASDFASDF');
    }

    public function testSingleDel(): void
    {
        self::log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");

        # make a comment
        send_event(new CommentPostingEvent($image_id, Ctx::$user, "Test Comment ASDFASDF"));
        self::get_page("post/view/$image_id");
        self::assert_text("ASDFASDF");

        # delete a comment
        $comment_id = (int)Ctx::$database->get_one("SELECT id FROM comments");
        send_event(new CommentDeletionEvent($comment_id));
        self::get_page("post/view/$image_id");
        self::assert_no_text("ASDFASDF");
    }
}
