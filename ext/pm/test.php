<?php

declare(strict_types=1);
class PrivMsgTest extends ShimmiePHPUnitTestCase
{
    public function testPM()
    {
        // Send from admin to user
        $this->log_in_as_admin();
        send_event(new SendPMEvent(new PM(
            User::by_name(self::$admin_name)->id,
            "0.0.0.0",
            User::by_name(self::$user_name)->id,
            "message demo to test"
        )));

        // Check that admin can see user's messages
        $this->get_page("user/" . self::$user_name);
        $this->assert_text("message demo to test");

        // Check that user can see own messages
        $this->log_in_as_user();
        $this->get_page("user");
        $this->assert_text("message demo to test");

        // FIXME: read PM
        // $this->get_page("pm/read/0");
        // $this->assert_text("No such PM");

        // FIXME: delete PM
        // send_event();
        // $this->get_page("user");
        // $this->assert_no_text("message demo to test");

        // FIXME: verify deleted
        // $this->get_page("pm/read/0");
        // $this->assert_text("No such PM");
    }
}
