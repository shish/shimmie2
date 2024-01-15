<?php

declare(strict_types=1);

namespace Shimmie2;

class DowntimeTest extends ShimmiePHPUnitTestCase
{
    public function tearDown(): void
    {
        global $config;
        $config->set_bool("downtime", false);
        parent::tearDown();
    }

    public function testDowntime(): void
    {
        global $config;

        $config->set_string("downtime_message", "brb, unit testing");

        // downtime on
        $config->set_bool("downtime", true);

        $this->log_in_as_admin();
        $this->get_page("post/list");
        $this->assert_text("DOWNTIME MODE IS ON!");
        $this->assert_response(200);

        $this->log_in_as_user();
        $this->get_page("post/list");
        $this->assert_content("brb, unit testing");
        $this->assert_response(503);

        // downtime off
        $config->set_bool("downtime", false);

        $this->log_in_as_admin();
        $this->get_page("post/list");
        $this->assert_no_text("DOWNTIME MODE IS ON!");
        $this->assert_response(200);

        $this->log_in_as_user();
        $this->get_page("post/list");
        $this->assert_no_content("brb, unit testing");
        $this->assert_response(200);
    }
}
