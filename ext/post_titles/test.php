<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostTitleTest extends ShimmiePHPUnitTestCase
{
    public function testTitleEdit(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);

        send_event(new ImageInfoSetEvent($image, 0, new QueryArray(["title" => "My Photo"])));

        self::get_page("post/view/$image_id");
        self::assert_text("My Photo");
    }

    public function testTitleSearch(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);

        send_event(new ImageInfoSetEvent($image, 0, new QueryArray(["title" => "My Photo"])));

        self::assert_search_results(["title:Photo"], [$image_id], "partial match");
    }
}
