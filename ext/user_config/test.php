<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserConfigTest extends ShimmiePHPUnitTestCase
{
    private const OPTIONS_BLOCK_TITLE = "User Options";

    public function testUserConfigPage(): void
    {
        self::assertException(PermissionDenied::class, function () {
            self::get_page('user_config');
        });

        self::log_in_as_user();
        self::get_page('user_config');
        self::assert_title(self::OPTIONS_BLOCK_TITLE);
        self::log_out();
    }
}
