<?php

declare(strict_types=1);

namespace Shimmie2;

class AdminPageTest extends ShimmiePHPUnitTestCase
{
    public function testAuth(): void
    {
        $this->log_out();
        $this->assertException(PermissionDenied::class, function () {
            $this->get_page('admin');
        });

        $this->log_in_as_user();
        $this->assertException(PermissionDenied::class, function () {
            $this->get_page('admin');
        });

        $this->log_in_as_admin();
        $page = $this->get_page('admin');
        $this->assertEquals(200, $page->code);
        $this->assertEquals("Admin Tools", $page->title);
    }

    public function testAct(): void
    {
        $this->log_in_as_admin();
        $page = $this->post_page('admin/test');
        $this->assertEquals("test", $page->data);
    }

    // does this belong here??
    public function testCliGen(): void
    {
        $app = new CliApp();
        send_event(new CliGenEvent($app));
        $this->assertTrue(true); // TODO: check for more than "no crash"?
    }
}
