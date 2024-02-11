<?php

declare(strict_types=1);

namespace Shimmie2;

class UserConfigTest extends ShimmiePHPUnitTestCase
{
    private const OPTIONS_BLOCK_TITLE = "User Options";

    public function testUserConfigPage(): void
    {
        $this->assertException(PermissionDenied::class, function () {
            $this->get_page('user_config');
        });

        $this->log_in_as_user();
        $this->get_page('user_config');
        $this->assert_title(self::OPTIONS_BLOCK_TITLE);
        $this->log_out();
    }
}
