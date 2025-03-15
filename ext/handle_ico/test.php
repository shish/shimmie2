<?php

declare(strict_types=1);

namespace Shimmie2;

final class IcoFileHandlerTest extends ShimmiePHPUnitTestCase
{
    public function testIcoHander(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("ext/static_files/static/favicon.ico", "shimmie favicon");

        $page = self::get_page("post/view/$image_id");
        self::assertEquals(200, $page->code);

        # FIXME: test that the thumb works
        # FIXME: test that it gets displayed properly
    }
}
