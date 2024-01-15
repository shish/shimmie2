<?php

declare(strict_types=1);

namespace Shimmie2;

class CommentListTest extends ShimmiePHPUnitTestCase
{
    public function setUp(): void
    {
        global $config;
        parent::setUp();
        $config->set_int("comment_limit", 100);
        $this->log_out();
    }

    public function tearDown(): void
    {
        global $config;
        $config->set_int("comment_limit", 10);
        parent::tearDown();
    }

    public function testCommentsPage(): void
    {
        global $user;

        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");

        # a good comment
        send_event(new CommentPostingEvent($image_id, $user, "Test Comment ASDFASDF"));
        $this->get_page("post/view/$image_id");
        $this->assert_text("ASDFASDF");

        # dupe
        try {
            send_event(new CommentPostingEvent($image_id, $user, "Test Comment ASDFASDF"));
        } catch (CommentPostingException $e) {
            $this->assertStringContainsString("try and be more original", $e->getMessage());
        }

        # empty comment
        try {
            send_event(new CommentPostingEvent($image_id, $user, ""));
        } catch (CommentPostingException $e) {
            $this->assertStringContainsString("Comments need text", $e->getMessage());
        }

        # whitespace is still empty...
        try {
            send_event(new CommentPostingEvent($image_id, $user, " \t\r\n"));
        } catch (CommentPostingException $e) {
            $this->assertStringContainsString("Comments need text", $e->getMessage());
        }

        # repetitive (aka. gzip gives >= 10x improvement)
        try {
            send_event(new CommentPostingEvent($image_id, $user, str_repeat("U", 5000)));
        } catch (CommentPostingException $e) {
            $this->assertStringContainsString("Comment too repetitive", $e->getMessage());
        }

        # test UTF8
        send_event(new CommentPostingEvent($image_id, $user, "Test Comment むちむち"));
        $this->get_page("post/view/$image_id");
        $this->assert_text("むちむち");

        # test that search by comment metadata works
        //		$this->get_page("post/list/commented_by=test/1");
        //		$this->assert_title("Image $image_id: pbx");
        //		$this->get_page("post/list/comments=2/1");
        //		$this->assert_title("Image $image_id: pbx");

        $this->log_out();

        $this->get_page('comment/list');
        $this->assert_title('Comments');
        $this->assert_text('ASDFASDF');

        $this->get_page('comment/list/2');
        $this->assert_title('Comments');

        $this->log_in_as_admin();
        $this->delete_image($image_id);
        $this->log_out();

        $this->get_page('comment/list');
        $this->assert_title('Comments');
        $this->assert_no_text('ASDFASDF');
    }

    public function testSingleDel(): void
    {
        global $database, $user;

        $this->log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");

        # make a comment
        send_event(new CommentPostingEvent($image_id, $user, "Test Comment ASDFASDF"));
        $this->get_page("post/view/$image_id");
        $this->assert_text("ASDFASDF");

        # delete a comment
        $comment_id = (int)$database->get_one("SELECT id FROM comments");
        send_event(new CommentDeletionEvent($comment_id));
        $this->get_page("post/view/$image_id");
        $this->assert_no_text("ASDFASDF");
    }
}
