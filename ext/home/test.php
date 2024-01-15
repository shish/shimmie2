<?php

declare(strict_types=1);

namespace Shimmie2;

class HomeTest extends ShimmiePHPUnitTestCase
{
    public function testHomePage(): void
    {
        $page = $this->get_page('home');
        $this->assertStringContainsString("Posts", $page->data);
    }
}
