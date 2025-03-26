<?php

declare(strict_types=1);

namespace Shimmie2;

final class PageTest extends ShimmiePHPUnitTestCase
{
    public function test_page(): void
    {
        $page = new Page();
        ob_start();
        $page->display();
        self::assertGreaterThan(0, ob_get_length());
        ob_end_clean();
    }

    public function test_file(): void
    {
        $page = new Page();
        $page->set_file(MimeType::JPEG, new Path("tests/pbx_screenshot.jpg"));
        ob_start();
        $page->display();
        self::assertGreaterThan(0, ob_get_length());
        ob_end_clean();
    }

    public function test_data(): void
    {
        $page = new Page();
        $page->set_data(MimeType::TEXT, "hello world");
        ob_start();
        $page->display();
        self::assertGreaterThan(0, ob_get_length());
        ob_end_clean();
    }

    public function test_redirect(): void
    {
        $page = new Page();
        $page->set_redirect(Url::parse("/new/page"));
        ob_start();
        $page->display();
        self::assertGreaterThan(0, ob_get_length());
        ob_end_clean();
    }

    public function test_subNav(): void
    {
        // the default theme doesn't send this, so let's have
        // a random test manually

        self::log_in_as_admin(); // show the most links

        $e = send_event(new PageSubNavBuildingEvent("system"));
        self::assertGreaterThan(0, count($e->links));

        $e = send_event(new PageSubNavBuildingEvent("posts"));
        self::assertGreaterThan(0, count($e->links));
    }
}
