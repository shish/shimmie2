<?php

declare(strict_types=1);

namespace Shimmie2;

final class BiographyTest extends ShimmiePHPUnitTestCase
{
    public function testBio(): void
    {
        self::log_in_as_user();
        self::post_page("user/" . self::USER_NAME . "/biography", ["biography" => "My bio goes here"]);
        self::get_page("user/" . self::USER_NAME);
        self::assert_text("My bio goes here");

        self::log_in_as_admin();
        self::get_page("user/" . self::USER_NAME);
        self::assert_text("My bio goes here");

        self::get_page("user/" . self::ADMIN_NAME);
        self::assert_no_text("My bio goes here");
    }
}
