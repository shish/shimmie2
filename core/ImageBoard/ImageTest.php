<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageTest extends ShimmiePHPUnitTestCase
{
    public function testLoadData(): void
    {
        self::log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "question? colon:thing exclamation!");
        $image = Image::by_id_ex($image_id_1);
        self::assertNull($image->source);
    }
}
