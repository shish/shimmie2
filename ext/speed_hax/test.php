<?php

declare(strict_types=1);

namespace Shimmie2;

class SpeedHaxTest extends ShimmiePHPUnitTestCase
{
    public function testUserNoTagLimit(): void
    {
        global $config;
        $config->set_int(SpeedHaxConfig::BIG_SEARCH, 1);

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
        $config->set_int(SpeedHaxConfig::BIG_SEARCH, 1);

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
        $config->set_int(SpeedHaxConfig::BIG_SEARCH, 1);

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
