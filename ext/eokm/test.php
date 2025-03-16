<?php

declare(strict_types=1);

namespace Shimmie2;

final class EokmTest extends ShimmiePHPUnitTestCase
{
    public function testPass(): void
    {
        // no EOKM login details set, so be a no-op
        self::log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        self::assert_no_text("Image too large");
        self::assert_no_text("Image too small");
        self::assert_no_text("ratio");
    }
}
