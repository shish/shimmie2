<?php

declare(strict_types=1);

namespace Shimmie2;

final class BrowserSearchTest extends ShimmiePHPUnitTestCase
{
    public function testBasic(): void
    {
        $page = self::get_page("browser_search.xml");
        self::assertSame(200, $page->code);

        $page = self::get_page("browser_search/test");
        self::assertSame(200, $page->code);
    }
}
