<?php

declare(strict_types=1);

namespace Shimmie2;

class PostSourceTest extends ShimmiePHPUnitTestCase
{
    public function testSourceEdit(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);

        send_event(new ImageInfoSetEvent($image, 0, ["source" => "example.com"]));
        send_event(new ImageInfoSetEvent($image, 0, ["source" => "http://example.com"]));

        $this->get_page("post/view/$image_id");
        $this->assert_text("example.com");
    }

    public function testSourceSearch(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);

        send_event(new ImageInfoSetEvent($image, 0, ["source" => "http://example.com"]));

        $this->assert_search_results(["source:http://example.com"], [$image_id], "exact match");
        $this->assert_search_results(["source:example.com"], [$image_id], "match without protocol");
        $this->assert_search_results(["source:https://example.com"], [$image_id], "match with wrong protocol");

        send_event(new ImageInfoSetEvent($image, 0, ["source" => "http://example.com/THING"]));

        $this->assert_search_results(["source:http://example.com"], [$image_id], "prefix match");
        $this->assert_search_results(["source:http://example.com/THING"], [$image_id], "case match");
        $this->assert_search_results(["source:http://example.com/thing"], [$image_id], "case mismatch");
    }
}
