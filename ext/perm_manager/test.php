<?php

declare(strict_types=1);

namespace Shimmie2;

class PermManagerTest extends ShimmiePHPUnitTestCase
{
    public function testAuth(): void
    {
        $this->log_in_as_admin();
        $this->get_page('perm_manager');
        $this->assert_title("User Classes");
    }
}
