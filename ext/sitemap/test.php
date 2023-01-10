<?php

declare(strict_types=1);

namespace Shimmie2;

class XMLSitemapTest extends ShimmiePHPUnitTestCase
{
    public function testBasic()
    {
        $page = $this->get_page('sitemap.xml');
        $this->assertEquals(200, $page->code);
    }
}
