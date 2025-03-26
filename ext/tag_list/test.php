<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagListTest extends ShimmiePHPUnitTestCase
{
    public function testIndex(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: pbx");
        self::assert_text("pbx");
    }
}
