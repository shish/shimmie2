<?php

declare(strict_types=1);

namespace Shimmie2;

class DowntimeTest extends ShimmiePHPUnitTestCase
{
    public function testDowntime(): void
    {
        global $config;

        $config->set_string("downtime_message", "brb, unit testing");

        // downtime on
        $config->set_bool("downtime", true);

        self::log_in_as_admin();
        self::get_page("post/list");
        self::assert_text("DOWNTIME MODE IS ON!");
        self::assert_response(200);

        self::log_in_as_user();
        self::get_page("post/list");
        self::assert_content("brb, unit testing");
        self::assert_response(503);

        // downtime off
        $config->set_bool("downtime", false);

        self::log_in_as_admin();
        self::get_page("post/list");
        self::assert_no_text("DOWNTIME MODE IS ON!");
        self::assert_response(200);

        self::log_in_as_user();
        self::get_page("post/list");
        self::assert_no_content("brb, unit testing");
        self::assert_response(200);
    }
}
