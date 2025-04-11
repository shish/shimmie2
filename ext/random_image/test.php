<?php

declare(strict_types=1);

namespace Shimmie2;

final class RandomImageTest extends ShimmiePHPUnitTestCase
{
    public function testRandom(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
        self::log_out();

        $page = self::get_page("random_image/view");
        self::assertEquals("Post $image_id: test", $page->title);

        $page = self::get_page("random_image/view/test");
        self::assertEquals("Post $image_id: test", $page->title);

        $page = self::get_page("random_image/download");
        self::assertEquals($page->mode, PageMode::FILE);
        # FIXME: assert($raw === file(blah.jpg))
    }

    public function testPostListBlock(): void
    {
        self::log_in_as_admin();

        # enabled, no image = no text
        Ctx::$config->set(RandomImageConfig::SHOW_RANDOM_BLOCK, true);
        $page = self::get_page("post/list");
        self::assertException(\Exception::class, function () use ($page) {$page->find_block("Random Post");});

        # enabled, image = text
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
        $page = self::get_page("post/list");
        $page->find_block("Random Post"); // will throw if missing

        # disabled, image = no text
        Ctx::$config->set(RandomImageConfig::SHOW_RANDOM_BLOCK, false);
        $page = self::get_page("post/list");
        self::assertException(\Exception::class, function () use ($page) {$page->find_block("Random Post");});

        # disabled, no image = no image
        $this->delete_image($image_id);
        $page = self::get_page("post/list");
        self::assertException(\Exception::class, function () use ($page) {$page->find_block("Random Post");});
    }
}
