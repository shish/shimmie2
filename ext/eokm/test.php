<?php

declare(strict_types=1);

namespace Shimmie2;

class EokmTest extends ShimmiePHPUnitTestCase
{
    public function testPass(): void
    {
        // no EOKM login details set, so be a no-op
        $this->log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->assert_no_text("Image too large");
        $this->assert_no_text("Image too small");
        $this->assert_no_text("ratio");
    }
}
