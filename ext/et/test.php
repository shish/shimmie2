<?php

declare(strict_types=1);

namespace Shimmie2;

final class ETTest extends ShimmiePHPUnitTestCase
{
    public function testET(): void
    {
        self::log_in_as_admin();
        self::get_page("system_info");
        self::assert_title("System Info");
    }
}
