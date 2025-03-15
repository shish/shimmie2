<?php

declare(strict_types=1);

namespace Shimmie2;

final class SiteDescriptionTest extends ShimmiePHPUnitTestCase
{
    public function testSiteDescription(): void
    {
        global $config, $page;
        $config->set_string("site_description", "A Shimmie testbed");
        self::get_page("post/list");
        self::assertStringContainsString(
            "<meta name='description' content='A Shimmie testbed' />",
            (string)$page->get_all_html_headers()
        );
    }

    public function testSiteKeywords(): void
    {
        global $config, $page;
        $config->set_string("site_keywords", "foo,bar,baz");
        self::get_page("post/list");
        self::assertStringContainsString(
            "<meta name='keywords' content='foo,bar,baz' />",
            (string)$page->get_all_html_headers()
        );
    }
}
