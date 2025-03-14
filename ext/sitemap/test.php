<?php

declare(strict_types=1);

namespace Shimmie2;

class XMLSitemapTest extends ShimmiePHPUnitTestCase
{
    public function testBasic(): void
    {
        // check empty DB
        @unlink(Filesystem::data_path("cache/sitemap.xml"));
        $page = self::get_page('sitemap.xml');
        self::assertEquals(200, $page->code);

        self::log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

        // check DB with one image
        @unlink(Filesystem::data_path("cache/sitemap.xml"));
        $page = self::get_page('sitemap.xml');
        self::assertEquals(200, $page->code);

        // check caching
        $page = self::get_page('sitemap.xml');
        self::assertEquals(200, $page->code);
    }
}
