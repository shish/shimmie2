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
        send_event(new UserLoginEvent(User::by_name(self::USER_NAME)));
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

    public function testUserNoTagLimit(): void
    {
        global $config;
        $config->set_int(IndexConfig::BIG_SEARCH, 1);

        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "asdf post1");
        $image_id_2 = $this->post_image("tests/favicon.png", "asdf post2");

        // default user isn't limited
        $this->assert_search_results(["asdf"], [$image_id_2, $image_id_1], "User can search for one tag");
        $this->assert_search_results(["asdf", "post1"], [$image_id_1], "User can search for two tags");
    }

    public function testAnonTagLimit(): void
    {
        global $config;
        $config->set_int(IndexConfig::BIG_SEARCH, 1);

        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "asdf post1");
        $image_id_2 = $this->post_image("tests/favicon.png", "asdf post2");
        $this->log_out();

        // default anon is limited
        $this->assert_search_results(["asdf"], [$image_id_2, $image_id_1], "Anon can search for one tag");
        $this->assertException(PermissionDenied::class, function () use ($image_id_1) {
            $this->assert_search_results(["asdf", "post1"], [$image_id_1]);
        });
    }

    public function testAnonPostNext(): void
    {
        global $config;
        $config->set_int(IndexConfig::BIG_SEARCH, 1);

        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "asdf post1");
        $image_id_2 = $this->post_image("tests/favicon.png", "asdf post2");
        $this->log_out();

        // post/next and post/prev use additional tags internally,
        // but those ones shouldn't count towards the limit
        $page = $this->get_page("post/next/$image_id_2", ["search" => "asdf"]);
        $this->assertEquals(PageMode::REDIRECT, $page->mode);
        $this->assertEquals($page->redirect, make_link("post/view/$image_id_1?#search=asdf"));
    }
}
