<?php

declare(strict_types=1);

namespace Shimmie2;

class TipsTest extends ShimmiePHPUnitTestCase
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
        $this->log_in_as_admin();

        $this->get_page("tips/list");
        $this->assert_title("Tips List");

        send_event(new CreateTipEvent(true, "", "a postless tip"));
        $this->get_page("post/list");
        $this->assert_text("a postless tip");

        $tip_id = (int)$database->get_one("SELECT id FROM tips");
        send_event(new DeleteTipEvent($tip_id));
        $this->get_page("post/list");
        $this->assert_no_text("a postless tip");
    }

    public function testImaged(): void
    {
        global $database;
        $this->log_in_as_admin();

        $this->get_page("tips/list");
        $this->assert_title("Tips List");

        send_event(new CreateTipEvent(true, "coins.png", "a postless tip"));
        $this->get_page("post/list");
        $this->assert_text("a postless tip");

        $tip_id = (int)$database->get_one("SELECT id FROM tips");
        send_event(new DeleteTipEvent($tip_id));
        $this->get_page("post/list");
        $this->assert_no_text("a postless tip");
    }

    public function testDisabled(): void
    {
        global $database;
        $this->log_in_as_admin();

        $this->get_page("tips/list");
        $this->assert_title("Tips List");

        send_event(new CreateTipEvent(false, "", "a postless tip"));
        $this->get_page("post/list");
        $this->assert_no_text("a postless tip");

        $tip_id = (int)$database->get_one("SELECT id FROM tips");
        send_event(new DeleteTipEvent($tip_id));
        $this->get_page("post/list");
        $this->assert_no_text("a postless tip");
    }
}
