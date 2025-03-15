<?php

declare(strict_types=1);

namespace Shimmie2;

final class LinkImageTest extends ShimmiePHPUnitTestCase
{
    public function testLinkImage(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pie");
        self::get_page("post/view/$image_id");

        $matches = [];
        self::assertNotFalse(\Safe\preg_match("#value='https?://.*/(post/view/[0-9]+)'#", $this->page_to_text(), $matches));
        $page = self::get_page($matches[1]);
        self::assertEquals("Post $image_id: pie", $page->title);
    }
}
