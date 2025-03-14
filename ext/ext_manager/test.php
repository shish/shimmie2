<?php

declare(strict_types=1);

namespace Shimmie2;

class ExtManagerTest extends ShimmiePHPUnitTestCase
{
    public function testAuth(): void
    {
        $this->get_page('ext_manager');
        self::assert_title("Extensions");

        $this->get_page('ext_doc');
        self::assert_title("Extensions");

        $this->get_page('ext_doc/ext_manager');
        self::assert_title("Documentation for Extension Manager");
        self::assert_text("view a list of all extensions");

        # test author without email
        $this->get_page('ext_doc/user');

        $this->log_in_as_admin();
        $this->get_page('ext_manager');
        self::assert_title("Extensions");
        //self::assert_text("SimpleTest integration"); // FIXME: something which still exists
        $this->log_out();

        # FIXME: test that some extensions can be added and removed? :S
    }
}
