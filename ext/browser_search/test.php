<?php

declare(strict_types=1);

namespace Shimmie2;

class BrowserSearchTest extends ShimmiePHPUnitTestCase
{
    public function testBasic(): void
    {
        $page = $this->get_page("browser_search.xml");
        $this->assertEquals(200, $page->code);

        $page = $this->get_page("browser_search/test");
        $this->assertEquals(200, $page->code);
    }
}
