<?php

declare(strict_types=1);

namespace Shimmie2;

class PostTagsTest extends ShimmiePHPUnitTestCase
{
    public function testValidChange(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);

        // Original
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: pbx");

        // Modified
        send_event(new TagSetEvent($image, ["new"]));
        $this->get_page("post/view/$image_id");
        $this->assert_title("Post $image_id: new");
    }

    public function testInvalidChange(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);

        $e = $this->assertException(TagSetException::class, function () use ($image) {
            send_event(new TagSetEvent($image, []));
        });
        $this->assertEquals("Tried to set zero tags", $e->getMessage());

        $e = $this->assertException(TagSetException::class, function () use ($image) {
            send_event(new TagSetEvent($image, ["*test*"]));
        });
        $this->assertEquals("Can't set a tag which contains a wildcard (*)", $e->getMessage());
    }

    public function testTagEdit_tooLong(): void
    {
        $this->log_in_as_user();
        $this->assertException(TagSetException::class, function () {
            $this->post_image("tests/pbx_screenshot.jpg", str_repeat("a", 500));
        });
    }
}
