<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageViewCounterTest extends ShimmiePHPUnitTestCase
{
    public function testPostView(): void
    {
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        self::log_in_as_admin();
        self::get_page("post/view/$image_id");
        self::assert_text("Views");
    }

    public function testPopular(): void
    {
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        self::get_page("post/view/$image_id");
        self::get_page("popular_images");
        self::assert_text("$image_id");
    }
}
