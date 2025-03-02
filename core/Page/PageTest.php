<?php

declare(strict_types=1);

namespace Shimmie2;

class PageTest extends ShimmiePHPUnitTestCase
{
    public function test_page(): void
    {
        $page = new Page();
        $page->set_mode(PageMode::PAGE);
        ob_start();
        $page->display();
        $this->assertGreaterThan(0, ob_get_length());
        ob_end_clean();
    }

    public function test_file(): void
    {
        $page = new Page();
        $page->set_mode(PageMode::FILE);
        $page->set_file("tests/pbx_screenshot.jpg");
        ob_start();
        $page->display();
        $this->assertGreaterThan(0, ob_get_length());
        ob_end_clean();
    }

    public function test_data(): void
    {
        $page = new Page();
        $page->set_mode(PageMode::DATA);
        $page->set_data("hello world");
        ob_start();
        $page->display();
        $this->assertGreaterThan(0, ob_get_length());
        ob_end_clean();
    }

    public function test_redirect(): void
    {
        $page = new Page();
        $page->set_mode(PageMode::REDIRECT);
        $page->set_redirect("/new/page");
        ob_start();
        $page->display();
        $this->assertGreaterThan(0, ob_get_length());
        ob_end_clean();
    }

    public function test_subNav(): void
    {
        // the default theme doesn't send this, so let's have
        // a random test manually

        $this->log_in_as_admin(); // show the most links

        $e = send_event(new PageSubNavBuildingEvent("system"));
        $this->assertGreaterThan(0, count($e->links));

        $e = send_event(new PageSubNavBuildingEvent("posts"));
        $this->assertGreaterThan(0, count($e->links));
    }
}
