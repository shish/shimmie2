<?php

declare(strict_types=1);

namespace Shimmie2;

final class ExtManagerTest extends ShimmiePHPUnitTestCase
{
    public function testAuth(): void
    {
        self::get_page('ext_manager');
        self::assert_title("Extensions");

        self::get_page('ext_doc');
        self::assert_title("Extensions");

        self::get_page('ext_doc/ext_manager');
        self::assert_title("Documentation for Extension Manager");
        self::assert_text("view a list of all extensions");

        # test author without email
        self::get_page('ext_doc/user');

        self::log_in_as_admin();
        self::get_page('ext_manager');
        self::assert_title("Extensions");
        //self::assert_text("SimpleTest integration"); // FIXME: something which still exists
        self::log_out();

        # FIXME: test that some extensions can be added and removed? :S
    }
}
