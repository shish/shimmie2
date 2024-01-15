<?php

declare(strict_types=1);

namespace Shimmie2;

class SystemTest extends ShimmiePHPUnitTestCase
{
    public function testView(): void
    {
        global $page;
        $this->get_page("system");
        $this->assertEquals(PageMode::REDIRECT, $page->mode);
    }
}
