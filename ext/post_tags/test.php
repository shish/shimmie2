<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostTagsTest extends ShimmiePHPUnitTestCase
{
    public function testValidChange(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);

        // Original
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: pbx");

        // Modified
        send_event(new TagSetEvent($image, ["new"]));
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: new");
    }

    public function testInvalidChange(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);

        $e = self::assertException(TagSetException::class, function () use ($image) {
            send_event(new TagSetEvent($image, []));
        });
        self::assertEquals("Tried to set zero tags", $e->getMessage());

        $e = self::assertException(TagSetException::class, function () use ($image) {
            send_event(new TagSetEvent($image, ["*test*"]));
        });
        self::assertEquals("Can't set a tag which contains a wildcard (*)", $e->getMessage());
    }

    public function testTagEdit_tooLong(): void
    {
        self::log_in_as_user();
        self::assertException(TagSetException::class, function () {
            $this->post_image("tests/pbx_screenshot.jpg", str_repeat("a", 500));
        });
    }
}
