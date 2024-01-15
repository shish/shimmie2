<?php

declare(strict_types=1);

namespace Shimmie2;

class ETTest extends ShimmiePHPUnitTestCase
{
    public function testET(): void
    {
        $this->log_in_as_admin();
        $this->get_page("system_info");
        $this->assert_title("System Info");
    }
}
