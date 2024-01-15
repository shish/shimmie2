<?php

declare(strict_types=1);

namespace Shimmie2;

class BlocksTest extends ShimmiePHPUnitTestCase
{
    public function testBlocks(): void
    {
        $this->log_in_as_admin();
        $this->get_page("blocks/list");
        $this->assert_response(200);
        $this->assert_title("Blocks");
    }
}
