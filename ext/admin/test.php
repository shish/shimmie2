<?php

declare(strict_types=1);

namespace Shimmie2;

final class AdminPageTest extends ShimmiePHPUnitTestCase
{
    public function testAuth(): void
    {
        self::log_out();
        self::assertException(PermissionDenied::class, function () {
            self::get_page('admin');
        });

        self::log_in_as_user();
        self::assertException(PermissionDenied::class, function () {
            self::get_page('admin');
        });

        self::log_in_as_admin();
        $page = self::get_page('admin');
        self::assertEquals(200, $page->code);
        self::assertEquals("Admin Tools", $page->title);
    }

    public function testAct(): void
    {
        self::log_in_as_admin();
        $page = self::post_page('admin/test');
        self::assertEquals("test", $page->data);
    }

    // does this belong here??
    public function testCliGen(): void
    {
        $app = new CliApp();
        $e = send_event(new CliGenEvent($app));
        self::assertFalse($e->stop_processing);
    }
}
