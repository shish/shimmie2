<?php

declare(strict_types=1);

namespace Shimmie2;

class SystemTest extends ShimmiePHPUnitTestCase
{
    public function testView(): void
    {
        global $page;
        self::get_page("system");
        self::assertEquals(PageMode::REDIRECT, $page->mode);
    }
}
