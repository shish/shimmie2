<?php

declare(strict_types=1);
class HomeTest extends ShimmiePHPUnitTestCase
{
    public function testHomePage()
    {
        $page = $this->get_page('home');
        $this->assertStringContainsString("Posts", $page->data);
    }
}
