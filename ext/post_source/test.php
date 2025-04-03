<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostSourceTest extends ShimmiePHPUnitTestCase
{
    public function testSourceEdit(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);

        send_event(new ImageInfoSetEvent($image, 0, new QueryArray(["source" => "example.com"])));
        send_event(new ImageInfoSetEvent($image, 0, new QueryArray(["source" => "http://example.com"])));

        self::get_page("post/view/$image_id");
        self::assert_text("example.com");
    }

    public function testSourceSearch(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);

        send_event(new ImageInfoSetEvent($image, 0, new QueryArray(["source" => "http://example.com"])));
        send_event(new ImageInfoSetEvent($image, 0, new QueryArray(["source" => "http://example.com/THING"])));

        self::assert_search_results(["source:http://example.com"], [$image_id], "exact match");
        self::assert_search_results(["source:example.com"], [$image_id], "match without protocol");
        self::assert_search_results(["source:https://example.com"], [$image_id], "match with wrong protocol");

        send_event(new ImageInfoSetEvent($image, 0, new QueryArray(["source" => "http://example.com/THING"])));

        self::assert_search_results(["source:http://example.com"], [$image_id], "prefix match");
        self::assert_search_results(["source:http://example.com/THING"], [$image_id], "case match");
        self::assert_search_results(["source:http://example.com/thing"], [$image_id], "case mismatch");
    }
}
