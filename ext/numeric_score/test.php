<?php

declare(strict_types=1);

namespace Shimmie2;

class NumericScoreTest extends ShimmiePHPUnitTestCase
{
    public function testNumericScore(): void
    {
        global $user;

        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $this->get_page("post/view/$image_id");
        $this->assert_text("Current Score: 0");

        send_event(new NumericScoreSetEvent($image_id, $user, -1));
        $this->get_page("post/view/$image_id");
        $this->assert_text("Current Score: -1");

        send_event(new NumericScoreSetEvent($image_id, $user, 1));
        $this->get_page("post/view/$image_id");
        $this->assert_text("Current Score: 1");

        # FIXME: test that up and down are hidden if already voted up or down

        # test search by score
        $page = $this->get_page("post/list/score=1/1");
        $this->assertEquals(PageMode::REDIRECT, $page->mode);

        $page = $this->get_page("post/list/score>0/1");
        $this->assertEquals(PageMode::REDIRECT, $page->mode);

        $page = $this->get_page("post/list/score>-5/1");
        $this->assertEquals(PageMode::REDIRECT, $page->mode);

        $page = $this->get_page("post/list/-score>5/1");
        $this->assertEquals(PageMode::REDIRECT, $page->mode);

        $page = $this->get_page("post/list/-score<-5/1");
        $this->assertEquals(PageMode::REDIRECT, $page->mode);

        # test search by vote
        $page = $this->get_page("post/list/upvoted_by=test/1");
        $this->assertEquals(PageMode::REDIRECT, $page->mode);

        # and downvote
        $page = $this->get_page("post/list/downvoted_by=test/1");
        $this->assertEquals(404, $page->code);

        # test errors
        $this->assertException(SearchTermParseException::class, function () {
            $this->get_page("post/list/upvoted_by=asdfasdf/1");
        });
        $this->assertException(SearchTermParseException::class, function () {
            $this->get_page("post/list/downvoted_by=asdfasdf/1");
        });

        $page = $this->get_page("post/list/upvoted_by_id=0/1");
        $this->assertEquals(404, $page->code);
        $page = $this->get_page("post/list/downvoted_by_id=0/1");
        $this->assertEquals(404, $page->code);
    }
}
