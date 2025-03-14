<?php

declare(strict_types=1);

namespace Shimmie2;

class BlotterTest extends ShimmiePHPUnitTestCase
{
    public function testDenial(): void
    {
        self::assertException(PermissionDenied::class, function () {
            $this->get_page("blotter/editor");
        });
        self::assertException(PermissionDenied::class, function () {
            $this->post_page("blotter/add");
        });
        self::assertException(PermissionDenied::class, function () {
            $this->post_page("blotter/remove");
        });
    }

    public function testAddViewRemove(): void
    {
        $this->log_in_as_admin();

        $page = $this->get_page("blotter/editor");
        self::assertEquals(200, $page->code);
        //$this->set_field("entry_text", "blotter testing");
        //$this->click("Add");
        //self::assert_text("blotter testing");

        $this->get_page("post/list");
        //self::assert_text("blotter testing");

        $this->get_page("blotter/editor");
        //$this->click("Remove");
        //self::assert_no_text("blotter testing");
    }
}
