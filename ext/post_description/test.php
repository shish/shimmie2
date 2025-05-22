<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostDescriptionTest extends ShimmiePHPUnitTestCase
{
    public function testDescription(): void
    {
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        self::log_in_as_admin();

        send_event(new PostDescriptionSetEvent($image_id, "This is a descriptive description."));
        self::get_page("post/view/$image_id");
        self::assert_text("descriptive description");

        send_event(new PostDescriptionSetEvent($image_id, "This is a changed description!"));
        self::get_page("post/view/$image_id");
        self::assert_text("changed description");
        self::assert_no_text("descriptive description");
    }
}
