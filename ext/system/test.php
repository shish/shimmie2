<?php

declare(strict_types=1);

namespace Shimmie2;

final class SystemTest extends ShimmiePHPUnitTestCase
{
    public function testView(): void
    {
        $page = self::get_page("system");
        self::assertEquals(PageMode::REDIRECT, $page->mode);
    }
}
