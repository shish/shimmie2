<?php declare(strict_types=1);

class BulkAddTest extends ShimmiePHPUnitTestCase
{
    public function testInvalidDir()
    {
        send_event(new UserLoginEvent(User::by_name(self::$admin_name)));
        $bae = send_event(new BulkAddEvent('asdf'));
        $this->assertContainsEquals(
            "Error, asdf is not a readable directory",
            $bae->results,
            implode("\n", $bae->results)
        );
    }

    public function testValidDir()
    {
        send_event(new UserLoginEvent(User::by_name(self::$admin_name)));
        send_event(new BulkAddEvent('tests'));
        $page = $this->get_page("post/list/hash=17fc89f372ed3636e28bd25cc7f3bac1/1");
        $this->assertEquals(302, $page->code);
    }
}
