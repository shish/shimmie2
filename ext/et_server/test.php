<?php

declare(strict_types=1);

namespace Shimmie2;

final class ETServerTest extends ShimmiePHPUnitTestCase
{
    public function testView(): void
    {
        self::post_page("register.php", ["data" => "test entry"]);

        self::log_in_as_user();
        self::assertException(ObjectNotFound::class, function () {
            self::get_page('register.php');
        });

        self::log_in_as_admin();
        self::get_page("et/stats");
        self::assert_text("Database Versions");

        self::log_in_as_admin();
        self::get_page("et/registrations");
        self::assert_text("test entry");
    }
}
