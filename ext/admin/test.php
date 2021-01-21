<?php declare(strict_types=1);
class AdminPageTest extends ShimmiePHPUnitTestCase
{
    public function testAuth()
    {
        send_event(new UserLoginEvent(User::by_name(self::$anon_name)));
        $page = $this->get_page('admin');
        $this->assertEquals(403, $page->code);
        $this->assertEquals("Permission Denied", $page->title);

        send_event(new UserLoginEvent(User::by_name(self::$user_name)));
        $page = $this->get_page('admin');
        $this->assertEquals(403, $page->code);
        $this->assertEquals("Permission Denied", $page->title);

        send_event(new UserLoginEvent(User::by_name(self::$admin_name)));
        $page = $this->get_page('admin');
        $this->assertEquals(200, $page->code);
        $this->assertEquals("Admin Tools", $page->title);
    }

    public function testCommands()
    {
        send_event(new UserLoginEvent(User::by_name(self::$admin_name)));
        ob_start();
        send_event(new CommandEvent(["index.php", "help"]));
        send_event(new CommandEvent(["index.php", "get-page", "post/list"]));
        send_event(new CommandEvent(["index.php", "post-page", "post/list", "foo=bar"]));
        send_event(new CommandEvent(["index.php", "get-token"]));
        send_event(new CommandEvent(["index.php", "regen-thumb", "42"]));
        ob_end_clean();

        // don't crash
        $this->assertTrue(true);
    }
}
