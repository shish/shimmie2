<?php

declare(strict_types=1);

namespace Shimmie2;

final class BlocksTest extends ShimmiePHPUnitTestCase
{
    public function testBlocks(): void
    {
        self::log_in_as_admin();
        self::get_page("blocks/list");
        self::assert_response(200);
        self::assert_title("Blocks");
    }
}
