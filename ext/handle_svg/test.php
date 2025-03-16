<?php

declare(strict_types=1);

namespace Shimmie2;

final class SVGFileHandlerTest extends ShimmiePHPUnitTestCase
{
    public function testSVGHander(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/test.svg", "something");
        self::get_page("post/view/$image_id"); // test for no crash
        self::get_page("get_svg/$image_id"); // test for no crash
        self::assert_content("www.w3.org");

        # FIXME: test that the thumb works
        # FIXME: test that it gets displayed properly
    }

    public function testAbusiveSVG(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/alert.svg", "something");
        self::get_page("post/view/$image_id");
        self::get_page("get_svg/$image_id");
        self::assert_no_content("script");
    }
}
