<?php

declare(strict_types=1);

namespace Shimmie2;

final class TipsTest extends ShimmiePHPUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Delete default tips so we can test from a blank slate
        global $database;
        $database->execute("DELETE FROM tips");
    }

    public function testImageless(): void
    {
        global $database;
        self::log_in_as_admin();

        self::get_page("tips/list");
        self::assert_title("Tips List");

        send_event(new CreateTipEvent(true, "", "a postless tip"));
        self::get_page("post/list");
        self::assert_text("a postless tip");

        $tip_id = (int)$database->get_one("SELECT id FROM tips");
        send_event(new DeleteTipEvent($tip_id));
        self::get_page("post/list");
        self::assert_no_text("a postless tip");
    }

    public function testImaged(): void
    {
        global $database;
        self::log_in_as_admin();

        self::get_page("tips/list");
        self::assert_title("Tips List");

        send_event(new CreateTipEvent(true, "coins.png", "a postless tip"));
        self::get_page("post/list");
        self::assert_text("a postless tip");

        $tip_id = (int)$database->get_one("SELECT id FROM tips");
        send_event(new DeleteTipEvent($tip_id));
        self::get_page("post/list");
        self::assert_no_text("a postless tip");
    }

    public function testDisabled(): void
    {
        global $database;
        self::log_in_as_admin();

        self::get_page("tips/list");
        self::assert_title("Tips List");

        send_event(new CreateTipEvent(false, "", "a postless tip"));
        self::get_page("post/list");
        self::assert_no_text("a postless tip");

        $tip_id = (int)$database->get_one("SELECT id FROM tips");
        send_event(new DeleteTipEvent($tip_id));
        self::get_page("post/list");
        self::assert_no_text("a postless tip");
    }
}
