<?php

declare(strict_types=1);

namespace Shimmie2;

final class PrivMsgTest extends ShimmiePHPUnitTestCase
{
    public function testUserReadOwnMessage(): void
    {
        // Send from admin to user
        self::log_in_as_admin();
        send_event(new SendPMEvent(new PM(
            User::by_name(self::ADMIN_NAME)->id,
            "0.0.0.0",
            User::by_name(self::USER_NAME)->id,
            "message demo to test",
            "test body"
        )));

        // Check that user can see own messages
        self::log_in_as_user();
        self::get_page("user/" . self::USER_NAME);
        self::assert_text("message demo to test");

        // FIXME: read PM
        // self::get_page("pm/read/0");
        // self::assert_text("No such PM");

        // FIXME: delete PM
        // send_event();
        // self::get_page("user");
        // self::assert_no_text("message demo to test");

        // FIXME: verify deleted
        // self::get_page("pm/read/0");
        // self::assert_text("No such PM");
    }

    public function testAdminReadOtherMessage(): void
    {
        // Send from admin to user
        self::log_in_as_admin();
        send_event(new SendPMEvent(new PM(
            User::by_name(self::ADMIN_NAME)->id,
            "0.0.0.0",
            User::by_name(self::USER_NAME)->id,
            "message demo to test",
            "test body"
        )));

        // Check that admin can see user's messages
        self::get_page("user/" . self::USER_NAME);
        self::assert_text("message demo to test");
    }
}
