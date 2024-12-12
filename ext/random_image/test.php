<?php

declare(strict_types=1);

namespace Shimmie2;

class RandomImageTest extends ShimmiePHPUnitTestCase
{
    public function testRandom(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
        $this->log_out();

        $page = $this->get_page("random_image/view");
        $this->assertEquals("Post $image_id: test", $page->title);

        $page = $this->get_page("random_image/view/test");
        $this->assertEquals("Post $image_id: test", $page->title);

        $page = $this->get_page("random_image/download");
        $this->assertEquals($page->mode, PageMode::FILE);
        # FIXME: assert($raw == file(blah.jpg))
    }

    public function testPostListBlock(): void
    {
        global $config;

        $this->log_in_as_admin();

        # enabled, no image = no text
        $config->set_bool("show_random_block", true);
        $page = $this->get_page("post/list");
        $this->assertException(\Exception::class, function () use ($page) {$page->find_block("Random Post");});

        # enabled, image = text
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
        $page = $this->get_page("post/list");
        $page->find_block("Random Post"); // will throw if missing

        # disabled, image = no text
        $config->set_bool("show_random_block", false);
        $page = $this->get_page("post/list");
        $this->assertException(\Exception::class, function () use ($page) {$page->find_block("Random Post");});

        # disabled, no image = no image
        $this->delete_image($image_id);
        $page = $this->get_page("post/list");
        $this->assertException(\Exception::class, function () use ($page) {$page->find_block("Random Post");});
    }
}
