<?php

declare(strict_types=1);

namespace Shimmie2;

class LogDatabaseTest extends ShimmiePHPUnitTestCase
{
    public function testLog(): void
    {
        $this->log_in_as_admin();
        $this->get_page("log/view");
        $this->get_page("log/view", ["r_module" => "core-image"]);
        $this->get_page("log/view", ["r_time" => "2012-03-01"]);
        $this->get_page("log/view", ["r_user" => "demo"]);

        $page = $this->get_page("log/view", ["r_priority" => "10"]);
        $this->assertEquals(200, $page->code);
    }
}
