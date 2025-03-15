<?php

declare(strict_types=1);

namespace Shimmie2;

final class RobotsTxtTest extends ShimmiePHPUnitTestCase
{
    public function testRobots(): void
    {
        $page = self::get_page("robots.txt");
        self::assertStringContainsString("User-agent: *", $page->data);
    }
}
