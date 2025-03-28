<?php

declare(strict_types=1);

namespace Shimmie2;

final class IndexTest extends ShimmiePHPUnitTestCase
{
    public function testIndexPage(): void
    {
        self::get_page('post/list');
        self::assert_title("Welcome to Shimmie");
        self::assert_no_text("Prev | Index | Next");

        self::log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->post_image("tests/bedroom_workshop.jpg", "thing computer computing bedroom workshop");
        self::log_out();

        self::get_page('post/list');
        self::assert_title("Shimmie");
        // FIXME
        //self::assert_text("Prev | Index | Next");

        self::get_page('post/list/-1');
        self::assert_title("Shimmie");

        self::get_page('post/list/0');
        self::assert_title("Shimmie");

        self::get_page('post/list/1');
        self::assert_title("Shimmie");

        self::assertException(PostNotFound::class, function () {
            self::get_page('post/view/99999');
        });

        # No results: 404
        self::assertException(PostNotFound::class, function () {
            self::get_page('post/list/maumaumau/1');
        });

        # One result: 302
        self::get_page("post/list/pbx/1");
        self::assert_response(302);

        # Multiple results: 200
        self::get_page('post/list/computer/1');
        self::assert_response(200);
    }

    // This isn't really an index thing, we just want to test this from
    // SOMEWHERE because the default theme doesn't use them.
    public function test_nav(): void
    {
        send_event(new UserLoginEvent(User::by_name(self::USER_NAME)));
        $e = send_event(new PageNavBuildingEvent());
        self::assertGreaterThan(0, count($e->links));
        // just a few common parents
        foreach (["help", "posts", "system", "user"] as $parent) {
            send_event(new PageSubNavBuildingEvent($parent));
        }
    }

    public function test_operands(): void
    {
        $e = new SearchTermParseEvent(0, null, []);
        self::assertFalse($e->negative);

        $e = new SearchTermParseEvent(1, "foo", ["foo"]);
        self::assertEquals("foo", $e->term);
        self::assertFalse($e->negative);

        $e = new SearchTermParseEvent(1, "-foo", ["-foo"]);
        self::assertEquals("foo", $e->term);
        self::assertTrue($e->negative);

        self::assertException(SearchTermParseException::class, function () {
            new SearchTermParseEvent(1, "", []);
        });

        self::assertException(SearchTermParseException::class, function () {
            new SearchTermParseEvent(1, "*", []);
        });
    }

    public function testUserNoTagLimit(): void
    {
        Ctx::$config->set(IndexConfig::BIG_SEARCH, 1);

        self::log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "asdf post1");
        $image_id_2 = $this->post_image("tests/favicon.png", "asdf post2");

        // default user isn't limited
        self::assert_search_results(["asdf"], [$image_id_2, $image_id_1], "User can search for one tag");
        self::assert_search_results(["asdf", "post1"], [$image_id_1], "User can search for two tags");
    }

    public function testAnonTagLimit(): void
    {
        Ctx::$config->set(IndexConfig::BIG_SEARCH, 1);

        self::log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "asdf post1");
        $image_id_2 = $this->post_image("tests/favicon.png", "asdf post2");
        self::log_out();

        // default anon is limited
        self::assert_search_results(["asdf"], [$image_id_2, $image_id_1], "Anon can search for one tag");
        self::assertException(PermissionDenied::class, function () use ($image_id_1) {
            self::assert_search_results(["asdf", "post1"], [$image_id_1]);
        });
    }

    public function testAnonPostNext(): void
    {
        Ctx::$config->set(IndexConfig::BIG_SEARCH, 1);

        self::log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "asdf post1");
        $image_id_2 = $this->post_image("tests/favicon.png", "asdf post2");
        self::log_out();

        // post/next and post/prev use additional tags internally,
        // but those ones shouldn't count towards the limit
        $page = self::get_page("post/next/$image_id_2", ["search" => "asdf"]);
        self::assertEquals(PageMode::REDIRECT, $page->mode);
        self::assertEquals($page->redirect, make_link("post/view/$image_id_1#search=asdf"));
    }
}
