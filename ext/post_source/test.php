<?php

declare(strict_types=1);

namespace Shimmie2;

class PostSourceTest extends ShimmiePHPUnitTestCase
{
    public function testSourceEdit(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id($image_id);

        send_event(new ImageInfoSetEvent($image, ["source" => "example.com"]));
        send_event(new ImageInfoSetEvent($image, ["source" => "http://example.com"]));

        $this->get_page("post/view/$image_id");
        $this->assert_text("example.com");
    }
}
