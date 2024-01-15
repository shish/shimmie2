<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageViewCounterTest extends ShimmiePHPUnitTestCase
{
    public function testPostView(): void
    {
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->log_in_as_admin();
        $this->get_page("post/view/$image_id");
        $this->assert_text("Views");
    }

    public function testPopular(): void
    {
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->get_page("post/view/$image_id");
        $this->get_page("popular_images");
        $this->assert_text("$image_id");
    }
}
