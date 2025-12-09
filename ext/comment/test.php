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

    public function testCommentLocking(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");

        # user can comment on unlocked post
        send_event(new CommentPostingEvent($image_id, Ctx::$user, "Test Comment Before Lock"));
        self::get_page("post/view/$image_id");
        self::assert_text("Before Lock");

        # admin locks comments
        self::log_in_as_admin();
        Ctx::$database->execute("UPDATE images SET comments_locked = TRUE WHERE id = :id", ["id" => $image_id]);

        # verify lock status
        $locked = (bool)Ctx::$database->get_one("SELECT comments_locked FROM images WHERE id = :id", ["id" => $image_id]);
        self::assertTrue($locked);

        # normal user cannot comment on locked post
        self::log_in_as_user();
        try {
            send_event(new CommentPostingEvent($image_id, Ctx::$user, "Test Comment After Lock"));
            self::fail("Expected CommentPostingException for locked comments");
        } catch (CommentPostingException $e) {
            self::assertStringContainsString("Comments are locked", $e->getMessage());
        }

        self::log_in_as_admin();

        # admin can bypass lock
        send_event(new CommentPostingEvent($image_id, Ctx::$user, "Admin Comment on Locked Post"));
        self::get_page("post/view/$image_id");
        self::assert_text("Admin Comment on Locked Post");

        # admin unlocks comments
        Ctx::$database->execute("UPDATE images SET comments_locked = FALSE WHERE id = :id", ["id" => $image_id]);

        # user can comment again after unlock
        self::log_in_as_user();
        send_event(new CommentPostingEvent($image_id, Ctx::$user, "Test Comment After Unlock"));
        self::get_page("post/view/$image_id");
        self::assert_text("After Unlock");
    }
}
