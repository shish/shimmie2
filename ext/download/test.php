<?php

declare(strict_types=1);

namespace Shimmie2;

class DownloadTest extends ShimmiePHPUnitTestCase
{
    public function testView()
    {
        global $page;
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->get_page("/image/$image_id");
        $this->assertEquals(PageMode::FILE, $page->mode);
    }
}
