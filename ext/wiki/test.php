<?php

declare(strict_types=1);

namespace Shimmie2;

class WikiTest extends ShimmiePHPUnitTestCase
{
    public function testIndex(): void
    {
        $page = $this->get_page("wiki");
        self::assertEquals(PageMode::REDIRECT, $page->mode);
    }

    // By default users are read-only
    public function testAccessUser(): void
    {
        $this->log_in_as_user();

        $this->get_page("wiki/test");
        self::assert_title("test");
        self::assert_text("This is a default page");

        self::assertException(PermissionDenied::class, function () {
            $this->get_page("wiki/test/edit");
        });
    }

    // Admins can edit
    public function testAccessAdmin(): void
    {
        $this->log_in_as_admin();

        $this->get_page("wiki/test");
        self::assert_title("test");
        self::assert_text("This is a default page");

        $this->get_page("wiki/test/edit");
        self::assert_text("Editor");
    }

    public function testDefault(): void
    {
        global $user;
        $this->log_in_as_admin();

        // Check default page is default
        $this->get_page("wiki/wiki:default");
        self::assert_title("wiki:default");
        self::assert_text("This is a default page");

        // Customise default page
        $wikipage = Wiki::get_page("wiki:default");
        $wikipage->revision = 1;
        $wikipage->body = "New Default Template";
        send_event(new WikiUpdateEvent($user, $wikipage));

        // Check that some random page is using the new default
        $this->get_page("wiki/something");
        self::assert_text("New Default Template");

        // Delete the custom default
        send_event(new WikiDeletePageEvent("wiki:default"));

        // Check that the default page is back to normal
        $this->get_page("wiki/wiki:default");
        self::assert_title("wiki:default");
        self::assert_text("This is a default page");
    }

    public function testRevisions(): void
    {
        global $user;
        $this->log_in_as_admin();

        $this->get_page("wiki/test");
        self::assert_title("test");
        self::assert_text("This is a default page");

        $wikipage = Wiki::get_page("test");
        $wikipage->revision = $wikipage->revision + 1;
        $wikipage->body = "Mooooo 1";
        send_event(new WikiUpdateEvent($user, $wikipage));
        $this->get_page("wiki/test");
        self::assert_text("Mooooo 1");
        self::assert_text("Revision 1");

        $wikipage = Wiki::get_page("test");
        $wikipage->revision = $wikipage->revision + 1;
        $wikipage->body = "Mooooo 2";
        send_event(new WikiUpdateEvent($user, $wikipage));
        $this->get_page("wiki/test");
        self::assert_text("Mooooo 2");
        self::assert_text("Revision 2");

        $this->get_page("wiki/test/history");
        self::assert_title("test");
        self::assert_text("2");

        send_event(new WikiDeleteRevisionEvent("test", 2));
        $this->get_page("wiki/test");
        self::assert_text("Mooooo 1");
        self::assert_text("Revision 1");

        send_event(new WikiDeletePageEvent("test"));
        $this->get_page("wiki/test");
        self::assert_title("test");
        self::assert_text("This is a default page");
    }
}
