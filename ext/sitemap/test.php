<?php

declare(strict_types=1);

namespace Shimmie2;

class XMLSitemapTest extends ShimmiePHPUnitTestCase
{
    public function testBasic(): void
    {
        $xml = Filesystem::data_path("cache/sitemap.xml");
        // check empty DB
        if ($xml->exists()) {
            $xml->unlink();
        }
        $page = $this->get_page('sitemap.xml');
        self::assertEquals(200, $page->code);

        $this->log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

        // check DB with one image
        if ($xml->exists()) {
            $xml->unlink();
        }
        $page = $this->get_page('sitemap.xml');
        self::assertEquals(200, $page->code);

        // check caching
        $page = $this->get_page('sitemap.xml');
        self::assertEquals(200, $page->code);
    }
}
