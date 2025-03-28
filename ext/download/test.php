<?php

declare(strict_types=1);

namespace Shimmie2;

final class DownloadTest extends ShimmiePHPUnitTestCase
{
    public function testView(): void
    {
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        self::get_page("image/$image_id/moo.jpg");
        self::assertEquals(PageMode::FILE, Ctx::$page->mode);
    }
}
