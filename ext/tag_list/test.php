<?php

declare(strict_types=1);

namespace Shimmie2;

class TagListTest extends ShimmiePHPUnitTestCase
{
    public function testIndex(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $page = $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: pbx");
        $this->assert_text("pbx");
    }
}
