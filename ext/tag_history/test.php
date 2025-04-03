<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Attributes\Depends;

final class TagHistoryTest extends ShimmiePHPUnitTestCase
{
    public function testHistoryWhenAdding(): void
    {
        // Set original
        self::log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "old_tag");
        Image::by_id_ex($image_id);

        // Check post
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: old_tag");

        // Check image history
        self::get_page("tag_history/$image_id");
        self::assert_title("Post $image_id Tag History");
        self::assert_text("old_tag");

        // Check global history
        self::get_page("tag_history/all/1");
        self::assert_title("Global Tag History");
        self::assert_text("old_tag");
    }

    #[Depends("testHistoryWhenAdding")]
    public function testHistoryWhenModifying(): void
    {
        // Set original
        self::log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "old_tag");
        $image = Image::by_id_ex($image_id);

        // Modify tags
        send_event(new TagSetEvent($image, ["new_tag"]));

        // Check post
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: new_tag");

        // Check image history
        self::get_page("tag_history/$image_id");
        self::assert_title("Post $image_id Tag History");
        self::assert_text("new_tag");

        // Check global history
        self::get_page("tag_history/all/1");
        self::assert_title("Global Tag History");
        self::assert_text("new_tag");
    }

    #[Depends("testHistoryWhenModifying")]
    public function testHistoryWhenReverting(): void
    {
        global $database;

        // Set original
        self::log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "old_tag");
        $image = Image::by_id_ex($image_id);
        $revert_id = $database->get_one(
            "SELECT id FROM tag_histories WHERE image_id = :image_id ORDER BY id DESC LIMIT 1",
            ["image_id" => $image_id],
        );

        // Modify tags
        send_event(new TagSetEvent($image, ["new_tag"]));

        // Revert tags
        self::post_page("tag_history/revert", ["revert" => (string)$revert_id]);

        // Check post
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: old_tag");

        // Check image history
        self::get_page("tag_history/$image_id");
        self::assert_title("Post $image_id Tag History");
        self::assert_text("old_tag");

        // Check global history
        self::get_page("tag_history/all/1");
        self::assert_title("Global Tag History");
        self::assert_text("old_tag");
    }
}
