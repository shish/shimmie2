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
        self::get_page("image/$image_id/foo.svg"); // test for no crash

        $image = Image::by_id_ex($image_id);
        self::assertStringContainsString("www.w3.org", $image->get_image_filename()->get_contents());

        $page = self::get_page("thumb/$image_id/foo.jpg"); // check thumbnail was generated
        self::assertEquals(200, $page->code);

        # FIXME: test that the thumb works
        # FIXME: test that it gets displayed properly
    }

    public function testAbusiveSVG(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/alert.svg", "something");
        self::get_page("post/view/$image_id");
        self::get_page("image/$image_id/foo.svg");

        $image = Image::by_id_ex($image_id);
        self::assertStringNotContainsString("script", $image->get_image_filename()->get_contents());

        $page = self::get_page("thumb/$image_id/foo.jpg"); // check thumbnail was generated
        self::assertEquals(200, $page->code);
    }
}
