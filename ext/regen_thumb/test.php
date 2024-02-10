<?php

declare(strict_types=1);

namespace Shimmie2;

class RegenThumbTest extends ShimmiePHPUnitTestCase
{
    public function testRegenThumb(): void
    {
        $this->log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->get_page("post/view/$image_id");

        $this->post_page("regen_thumb/one/$image_id");
        $this->assert_title("Thumbnail Regenerated");

        # FIXME: test that the thumb's modified time has been updated
    }
}
