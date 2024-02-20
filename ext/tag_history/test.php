<?php

declare(strict_types=1);

namespace Shimmie2;

class TagHistoryTest extends ShimmiePHPUnitTestCase
{
    public function testTagHistory(): void
    {
        $this->log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "old_tag");
        $image = Image::by_id_ex($image_id);

        // Original
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: old_tag");

        // Modified
        send_event(new TagSetEvent($image, ["new_tag"]));

        // FIXME
        // $this->click("View Tag History");
        // $this->assert_text("new (Set by demo");
        // $this->click("Revert To");
        // $this->get_page("post/view/$image_id");
        // $this->assert_title("Image $image_id: pbx");

        $this->get_page("tag_history/all/1");
        $this->assert_title("Global Tag History");
        $this->assert_text("new_tag");
    }
}
