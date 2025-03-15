<?php

declare(strict_types=1);

namespace Shimmie2;

final class HomeTest extends ShimmiePHPUnitTestCase
{
    public function testHomePage(): void
    {
        $page = self::get_page('home');
        self::assertStringContainsString("Posts", $page->data);
    }
}
