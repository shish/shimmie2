<?php

declare(strict_types=1);

namespace Shimmie2;

class AdminPageTest extends ShimmiePHPUnitTestCase
{
    public function testAuth(): void
    {
        $this->log_out();
        self::assertException(PermissionDenied::class, function () {
            $this->get_page('admin');
        });

        $this->log_in_as_user();
        self::assertException(PermissionDenied::class, function () {
            $this->get_page('admin');
        });

        $this->log_in_as_admin();
        $page = $this->get_page('admin');
        self::assertEquals(200, $page->code);
        self::assertEquals("Admin Tools", $page->title);
    }

    public function testAct(): void
    {
        $this->log_in_as_admin();
        $page = $this->post_page('admin/test');
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
