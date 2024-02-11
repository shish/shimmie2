<?php

declare(strict_types=1);

namespace Shimmie2;

class IPBanTest extends ShimmiePHPUnitTestCase
{
    # FIXME: test that the IP is actually banned

    public function testAccess(): void
    {
        $this->assertException(PermissionDenied::class, function () {
            $this->get_page('ip_ban/list');
        });
    }

    public function testIPBan(): void
    {
        global $database;

        $this->log_in_as_admin();

        // Check initial state
        $this->get_page('ip_ban/list');
        $this->assert_no_text("42.42.42.42");

        // Add ban
        send_event(new AddIPBanEvent(
            '42.42.42.42',
            'block',
            'unit testing',
            '2030-01-01'
        ));

        // Check added
        $page = $this->get_page('ip_ban/list');
        $this->assertStringContainsString(
            "42.42.42.42",
            $page->find_block("Edit IP Bans")->body
        );

        // Delete ban
        $ban_id = (int)$database->get_one("SELECT id FROM bans");
        send_event(new RemoveIPBanEvent($ban_id));

        // Check delete
        $page = $this->get_page('ip_ban/list');
        $this->assertStringNotContainsString(
            "42.42.42.42",
            $page->find_block("Edit IP Bans")->body
        );
    }

    public function test_all(): void
    {
        // just test it doesn't crash for now
        $this->log_in_as_admin();
        $page = $this->get_page('ip_ban/list', ['r_all' => 'on']);
        $this->assertEquals(200, $page->code);
    }
}
