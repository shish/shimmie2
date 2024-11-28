<?php

declare(strict_types=1);

namespace Shimmie2;

class IndexTest extends ShimmiePHPUnitTestCase
{
    public function testIndexPage(): void
    {
        $this->get_page('post/list');
        $this->assert_title("Welcome to Shimmie");
        $this->assert_no_text("Prev | Index | Next");

        $this->log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->post_image("tests/bedroom_workshop.jpg", "thing computer computing bedroom workshop");
        $this->log_out();

        $this->get_page('post/list');
        $this->assert_title("Shimmie");
        // FIXME
        //$this->assert_text("Prev | Index | Next");

        $this->get_page('post/list/-1');
        $this->assert_title("Shimmie");

        $this->get_page('post/list/0');
        $this->assert_title("Shimmie");

        $this->get_page('post/list/1');
        $this->assert_title("Shimmie");

        $this->assertException(PostNotFound::class, function () {
            $this->get_page('post/view/99999');
        });

        # No results: 404
        $this->assertException(PostNotFound::class, function () {
            $this->get_page('post/list/maumaumau/1');
        });

        # One result: 302
        $this->get_page("post/list/pbx/1");
        $this->assert_response(302);

        # Multiple results: 200
        $this->get_page('post/list/computer/1');
        $this->assert_response(200);
    }

    // This isn't really an index thing, we just want to test this from
    // SOMEWHERE because the default theme doesn't use them.
    public function test_nav(): void
    {
        send_event(new UserLoginEvent(User::by_name(self::$user_name)));
        $e = send_event(new PageNavBuildingEvent());
        $this->assertGreaterThan(0, count($e->links));
        // just a few common parents
        foreach (["help", "posts", "system", "user"] as $parent) {
            send_event(new PageSubNavBuildingEvent($parent));
        }
    }

    public function test_operands(): void
    {
        $e = new SearchTermParseEvent(0, null, []);
        $this->assertFalse($e->negative);

        $e = new SearchTermParseEvent(1, "foo", ["foo"]);
        $this->assertEquals("foo", $e->term);
        $this->assertFalse($e->negative);

        $e = new SearchTermParseEvent(1, "-foo", ["-foo"]);
        $this->assertEquals("foo", $e->term);
        $this->assertTrue($e->negative);

        $this->assertException(SearchTermParseException::class, function () {
            new SearchTermParseEvent(1, "", []);
        });

        $this->assertException(SearchTermParseException::class, function () {
            new SearchTermParseEvent(1, "*", []);
        });
    }
}
