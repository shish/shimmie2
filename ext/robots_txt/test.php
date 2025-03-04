<?php

declare(strict_types=1);

namespace Shimmie2;

class RobotsTxtTest extends ShimmiePHPUnitTestCase
{
    public function testRobots(): void
    {
        $page = $this->get_page("robots.txt");
        $this->assertStringContainsString("User-agent: *", $page->data);
    }
}
