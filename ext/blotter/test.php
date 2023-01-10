<?php

declare(strict_types=1);

namespace Shimmie2;

class BlotterTest extends ShimmiePHPUnitTestCase
{
    public function testDenial()
    {
        $this->get_page("blotter/editor");
        $this->assert_response(403);
        $this->get_page("blotter/add");
        $this->assert_response(403);
        $this->get_page("blotter/remove");
        $this->assert_response(403);
    }

    public function testAddViewRemove()
    {
        $this->log_in_as_admin();

        $page = $this->get_page("blotter/editor");
        $this->assertEquals(200, $page->code);
        //$this->set_field("entry_text", "blotter testing");
        //$this->click("Add");
        //$this->assert_text("blotter testing");

        $this->get_page("blotter");
        //$this->assert_text("blotter testing");

        $this->get_page("blotter/editor");
        //$this->click("Remove");
        //$this->assert_no_text("blotter testing");
    }
}
