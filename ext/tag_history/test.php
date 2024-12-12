<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Attributes\Depends;

class TagHistoryTest extends ShimmiePHPUnitTestCase
{
    public function testHistoryWhenAdding(): void
    {
        // Set original
        $this->log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "old_tag");
        $image = Image::by_id_ex($image_id);

        // Check post
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: old_tag");

        // Check image history
        $this->get_page("tag_history/$image_id");
        $this->assert_title("Post $image_id Tag History");
        $this->assert_text("old_tag");

        // Check global history
        $this->get_page("tag_history/all/1");
        $this->assert_title("Global Tag History");
        $this->assert_text("old_tag");
    }

    #[Depends("testHistoryWhenAdding")]
    public function testHistoryWhenModifying(): void
    {
        // Set original
        $this->log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "old_tag");
        $image = Image::by_id_ex($image_id);

        // Modify tags
        send_event(new TagSetEvent($image, ["new_tag"]));

        // Check post
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: new_tag");

        // Check image history
        $this->get_page("tag_history/$image_id");
        $this->assert_title("Post $image_id Tag History");
        $this->assert_text("new_tag");

        // Check global history
        $this->get_page("tag_history/all/1");
        $this->assert_title("Global Tag History");
        $this->assert_text("new_tag");
    }

    #[Depends("testHistoryWhenModifying")]
    public function testHistoryWhenReverting(): void
    {
        global $database;

        // Set original
        $this->log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "old_tag");
        $image = Image::by_id_ex($image_id);
        $revert_id = $database->get_one(
            "SELECT id FROM tag_histories WHERE image_id = :image_id ORDER BY id DESC LIMIT 1",
            ["image_id" => $image_id],
        );

        // Modify tags
        send_event(new TagSetEvent($image, ["new_tag"]));

        // Revert tags
        $this->post_page("tag_history/revert", ["revert" => $revert_id]);

        // Check post
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: old_tag");

        // Check image history
        $this->get_page("tag_history/$image_id");
        $this->assert_title("Post $image_id Tag History");
        $this->assert_text("old_tag");

        // Check global history
        $this->get_page("tag_history/all/1");
        $this->assert_title("Global Tag History");
        $this->assert_text("old_tag");
    }
}
