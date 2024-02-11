<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

require_once "core/basepage.php";

class BasePageTest extends TestCase
{
    public function test_page(): void
    {
        $page = new BasePage();
        $page->set_mode(PageMode::PAGE);
        ob_start();
        $page->display();
        ob_end_clean();
        $this->assertTrue(true);  // doesn't crash
    }

    public function test_file(): void
    {
        $page = new BasePage();
        $page->set_mode(PageMode::FILE);
        $page->set_file("tests/pbx_screenshot.jpg");
        ob_start();
        $page->display();
        ob_end_clean();
        $this->assertTrue(true);  // doesn't crash
    }

    public function test_data(): void
    {
        $page = new BasePage();
        $page->set_mode(PageMode::DATA);
        $page->set_data("hello world");
        ob_start();
        $page->display();
        ob_end_clean();
        $this->assertTrue(true);  // doesn't crash
    }

    public function test_redirect(): void
    {
        $page = new BasePage();
        $page->set_mode(PageMode::REDIRECT);
        $page->set_redirect("/new/page");
        ob_start();
        $page->display();
        ob_end_clean();
        $this->assertTrue(true);  // doesn't crash
    }

    public function test_subNav(): void
    {
        // the default theme doesn't send this, so let's have
        // a random test manually
        send_event(new PageSubNavBuildingEvent("system"));
        $this->assertTrue(true);  // doesn't crash
    }
}
