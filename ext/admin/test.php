<?php

declare(strict_types=1);

namespace Shimmie2;

class AdminPageTest extends ShimmiePHPUnitTestCase
{
    public function testAuth(): void
    {
        send_event(new UserLoginEvent(User::by_name(self::$anon_name)));
        $this->assertException(PermissionDeniedException::class, function () {
            $this->get_page('admin');
        });

        send_event(new UserLoginEvent(User::by_name(self::$user_name)));
        $this->assertException(PermissionDeniedException::class, function () {
            $this->get_page('admin');
        });

        send_event(new UserLoginEvent(User::by_name(self::$admin_name)));
        $page = $this->get_page('admin');
        $this->assertEquals(200, $page->code);
        $this->assertEquals("Admin Tools", $page->title);
    }
}
