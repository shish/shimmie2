<?php

declare(strict_types=1);

namespace Shimmie2;

class TombstonesTest extends ShimmiePHPUnitTestCase
{
    public function testDelete(): void
    {
        self::log_in_as_admin();

        // Post image
        $image_id = self::post_image("tests/pbx_screenshot.jpg", "pbx");
        $page = self::get_page("post/view/$image_id");
        self::assertEquals(200, $page->code);

        // Ban & delete
        $image = Image::by_id_ex($image_id);
        send_event(new AddImageHashBanEvent($image->hash, "test hash ban"));
        send_event(new ImageDeletionEvent($image, true));

        // Check deleted
        self::assertException(PostNotFound::class, function () use ($image_id) {
            self::get_page("post/view/$image_id");
        });
    }
}
