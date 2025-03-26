<?php

declare(strict_types=1);

namespace Shimmie2;

final class NumericScoreTest extends ShimmiePHPUnitTestCase
{
    public function testNumericScore(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        self::get_page("post/view/$image_id");
        self::assert_text("Post Score: 0");

        send_event(new NumericScoreSetEvent($image_id, Ctx::$user, -1));
        self::get_page("post/view/$image_id");
        self::assert_text("Post Score: -1");

        send_event(new NumericScoreSetEvent($image_id, Ctx::$user, 1));
        self::get_page("post/view/$image_id");
        self::assert_text("Post Score: 1");

        # FIXME: test that up and down are hidden if already voted up or down

        # test search by score
        $page = self::get_page("post/list/score=1/1");
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        $page = self::get_page("post/list/score>0/1");
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        $page = self::get_page("post/list/score>-5/1");
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        $page = self::get_page("post/list/-score>5/1");
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        $page = self::get_page("post/list/-score<-5/1");
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        # test search by vote
        $page = self::get_page("post/list/upvoted_by=test/1");
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        # and downvote
        self::assertException(PostNotFound::class, function () {
            self::get_page("post/list/downvoted_by=test/1");
        });

        # test errors
        self::assertException(UserNotFound::class, function () {
            self::get_page("post/list/upvoted_by=asdfasdf/1");
        });
        self::assertException(UserNotFound::class, function () {
            self::get_page("post/list/downvoted_by=asdfasdf/1");
        });
        self::assertException(PostNotFound::class, function () {
            self::get_page("post/list/upvoted_by_id=0/1");
        });
        self::assertException(PostNotFound::class, function () {
            self::get_page("post/list/downvoted_by_id=0/1");
        });
    }
}
