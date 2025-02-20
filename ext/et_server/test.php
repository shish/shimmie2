<?php

declare(strict_types=1);

namespace Shimmie2;

class ETServerTest extends ShimmiePHPUnitTestCase
{
    public function testView(): void
    {
        $this->post_page("register.php", ["data" => "test entry"]);

        $this->log_in_as_user();
        $this->assertException(ObjectNotFound::class, function () {
            $this->get_page('register.php');
        });

        $this->log_in_as_admin();
        $this->get_page("register.php");
        $this->assert_text("test entry");
    }
}
