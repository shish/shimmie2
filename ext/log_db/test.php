<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogDatabaseTest extends ShimmiePHPUnitTestCase
{
    public function testLog(): void
    {
        self::log_in_as_admin();
        self::get_page("log/view");
        self::get_page("log/view", ["r_module" => "core-image"]);
        self::get_page("log/view", ["r_time" => "2012-03-01"]);
        self::get_page("log/view", ["r_user" => "demo"]);

        $page = self::get_page("log/view", ["r_priority" => "10"]);
        self::assertEquals(200, $page->code);
    }
}
