<?php

declare(strict_types=1);

namespace Shimmie2;

class PixelFileHandlerTest extends ShimmiePHPUnitTestCase
{
    public function testPixelHander(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $page = $this->get_page("post/view/$image_id");
        $this->assertEquals(200, $page->code);
        //$this->assert_response(302);

        # FIXME: test that the thumb works
        # FIXME: test that it gets displayed properly
    }
}
