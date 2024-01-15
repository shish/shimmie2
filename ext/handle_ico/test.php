<?php

declare(strict_types=1);

namespace Shimmie2;

class IcoFileHandlerTest extends ShimmiePHPUnitTestCase
{
    public function testIcoHander(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("ext/static_files/static/favicon.ico", "shimmie favicon");

        $page = $this->get_page("post/view/$image_id");
        $this->assertEquals(200, $page->code);

        # FIXME: test that the thumb works
        # FIXME: test that it gets displayed properly
    }
}
