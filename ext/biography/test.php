<?php

declare(strict_types=1);

namespace Shimmie2;

class BiographyTest extends ShimmiePHPUnitTestCase
{
    public function testBio(): void
    {
        $this->log_in_as_user();
        $this->post_page("user/" . self::USER_NAME . "/biography", ["biography" => "My bio goes here"]);
        $this->get_page("user/" . self::USER_NAME);
        $this->assert_text("My bio goes here");

        $this->log_in_as_admin();
        $this->get_page("user/" . self::USER_NAME);
        $this->assert_text("My bio goes here");

        $this->get_page("user/" . self::ADMIN_NAME);
        $this->assert_no_text("My bio goes here");
    }
}
