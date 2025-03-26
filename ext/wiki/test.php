<?php

declare(strict_types=1);

namespace Shimmie2;

final class WikiTest extends ShimmiePHPUnitTestCase
{
    public function testIndex(): void
    {
        $page = self::get_page("wiki");
        self::assertEquals(PageMode::REDIRECT, $page->mode);
    }

    // By default users are read-only
    public function testAccessUser(): void
    {
        self::log_in_as_user();

        self::get_page("wiki/test");
        self::assert_title("test");
        self::assert_text("This is a default page");

        self::assertException(PermissionDenied::class, function () {
            self::get_page("wiki/test/edit");
        });
    }

    // Admins can edit
    public function testAccessAdmin(): void
    {
        self::log_in_as_admin();

        self::get_page("wiki/test");
        self::assert_title("test");
        self::assert_text("This is a default page");

        self::get_page("wiki/test/edit");
        self::assert_text("Editor");
    }

    public function testDefault(): void
    {
        self::log_in_as_admin();

        // Check default page is default
        self::get_page("wiki/wiki:default");
        self::assert_title("wiki:default");
        self::assert_text("This is a default page");

        // Customise default page
        $wikipage = Wiki::get_page("wiki:default");
        $wikipage->revision = 1;
        $wikipage->body = "New Default Template";
        send_event(new WikiUpdateEvent(Ctx::$user, $wikipage));

        // Check that some random page is using the new default
        self::get_page("wiki/something");
        self::assert_text("New Default Template");

        // Delete the custom default
        send_event(new WikiDeletePageEvent("wiki:default"));

        // Check that the default page is back to normal
        self::get_page("wiki/wiki:default");
        self::assert_title("wiki:default");
        self::assert_text("This is a default page");
    }

    public function testRevisions(): void
    {
        self::log_in_as_admin();

        self::get_page("wiki/test");
        self::assert_title("test");
        self::assert_text("This is a default page");

        $wikipage = Wiki::get_page("test");
        $wikipage->revision = $wikipage->revision + 1;
        $wikipage->body = "Mooooo 1";
        send_event(new WikiUpdateEvent(Ctx::$user, $wikipage));
        self::get_page("wiki/test");
        self::assert_text("Mooooo 1");
        self::assert_text("Revision 1");

        $wikipage = Wiki::get_page("test");
        $wikipage->revision = $wikipage->revision + 1;
        $wikipage->body = "Mooooo 2";
        send_event(new WikiUpdateEvent(Ctx::$user, $wikipage));
        self::get_page("wiki/test");
        self::assert_text("Mooooo 2");
        self::assert_text("Revision 2");

        self::get_page("wiki/test/history");
        self::assert_title("test");
        self::assert_text("2");

        send_event(new WikiDeleteRevisionEvent("test", 2));
        self::get_page("wiki/test");
        self::assert_text("Mooooo 1");
        self::assert_text("Revision 1");

        send_event(new WikiDeletePageEvent("test"));
        self::get_page("wiki/test");
        self::assert_title("test");
        self::assert_text("This is a default page");
    }
}
