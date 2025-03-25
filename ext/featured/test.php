<?php

declare(strict_types=1);

namespace Shimmie2;

final class FeaturedTest extends ShimmiePHPUnitTestCase
{
    public function testFeatured(): void
    {
        // Set up
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");

        # FIXME: test that regular users can't feature things

        // Admin can feature things
        self::log_in_as_admin();
        $page = self::post_page("featured_image/set/$image_id");
        self::assertEquals(302, $page->code);

        self::get_page("post/list");
        self::assert_text("Featured Post");

        # FIXME: test changing from one feature to another

        $page = self::get_page("featured_image/download");
        self::assertEquals(200, $page->code);

        $page = self::get_page("featured_image/view");
        self::assertEquals(200, $page->code);

        // after deletion, there should be no feature
        $this->delete_image($image_id);
        self::get_page("post/list");
        self::assert_no_text("Featured Post");
    }
}
