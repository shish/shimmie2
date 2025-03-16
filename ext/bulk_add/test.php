<?php

declare(strict_types=1);

namespace Shimmie2;

final class BulkAddTest extends ShimmiePHPUnitTestCase
{
    public function testInvalidDir(): void
    {
        send_event(new UserLoginEvent(User::by_name(self::ADMIN_NAME)));
        $bae = send_event(new BulkAddEvent(new Path('asdf')));
        self::assertTrue(is_a($bae->results[0], UploadError::class));
    }

    public function testValidDir(): void
    {
        send_event(new UserLoginEvent(User::by_name(self::ADMIN_NAME)));
        send_event(new BulkAddEvent(new Path('tests')));
        $page = self::get_page("post/list/hash=17fc89f372ed3636e28bd25cc7f3bac1/1");
        self::assertEquals(302, $page->code);
    }
}
