<?php

declare(strict_types=1);

namespace Shimmie2;

final class BlotterTest extends ShimmiePHPUnitTestCase
{
    public function testDenial(): void
    {
        self::assertException(PermissionDenied::class, function () {
            self::get_page("blotter/editor");
        });
        self::assertException(PermissionDenied::class, function () {
            self::post_page("blotter/add");
        });
        self::assertException(PermissionDenied::class, function () {
            self::post_page("blotter/remove");
        });
    }

    public function testAddViewRemove(): void
    {
        self::log_in_as_admin();

        $page = self::get_page("blotter/editor");
        self::assertEquals(200, $page->code);
        //$this->set_field("entry_text", "blotter testing");
        //$this->click("Add");
        //self::assert_text("blotter testing");

        self::get_page("post/list");
        //self::assert_text("blotter testing");

        self::get_page("blotter/editor");
        //$this->click("Remove");
        //self::assert_no_text("blotter testing");
    }
}
