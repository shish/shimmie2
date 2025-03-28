<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageTest extends ShimmiePHPUnitTestCase
{
    public function testLoadData(): void
    {
        self::log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "AC/DC");
        $image = Image::by_id_ex($image_id_1);
        self::assertNull($image->source);
        self::assertEquals("pbx_screenshot.jpg", $image->filename);

        Ctx::$config->set(SetupConfig::NICE_URLS, true);
        self::assertEquals(
            "/test/_images/feb01bab5698a11dd87416724c7a89e3/1%20-%20ACDC.jpg",
            (string)$image->get_image_link()
        );
        self::assertEquals(
            "/test/_thumbs/feb01bab5698a11dd87416724c7a89e3/thumb.jpg",
            (string)$image->get_thumb_link()
        );

        Ctx::$config->set(SetupConfig::NICE_URLS, false);
        self::assertEquals(
            "/test/index.php?q=image%2F1%2F1%2520-%2520ACDC.jpg",
            (string)$image->get_image_link()
        );
        self::assertEquals(
            "/test/index.php?q=thumb%2F1%2Fthumb.jpg",
            (string)$image->get_thumb_link()
        );
    }
}
