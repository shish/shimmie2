<?php

declare(strict_types=1);

namespace Shimmie2;

final class ExtManagerTest extends ShimmiePHPUnitTestCase
{
    public function testDoc(): void
    {
        self::get_page('ext_doc/ext_manager');
        self::assert_title("Documentation for Extension Manager");
        self::assert_text("(This extension has no documentation)");

        # test author without email
        self::get_page('ext_doc/user');
    }

    public function testManage(): void
    {
        self::log_in_as_admin();
        self::get_page('ext_manager');
        self::assert_title("Extensions");
        self::assert_text("Image Files");
        self::log_out();

        # FIXME: test that some extensions can be added and removed? :S
    }
}
