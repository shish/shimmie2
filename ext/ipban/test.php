<?php
class IPBanTest extends ShimmiePHPUnitTestCase
{
    public function testIPBan()
    {
        $this->get_page('ip_ban/list');
        $this->assert_response(403);
        $this->assert_title("Permission Denied");

        $this->log_in_as_admin();

        $this->get_page('ip_ban/list');
        $this->assert_no_text("42.42.42.42");

        $this->markTestIncomplete();

        $this->set_field('c_ip', '42.42.42.42');
        $this->set_field('c_reason', 'unit testing');
        $this->set_field('c_expires', '1 week');
        $this->click("Ban");

        $this->assert_text("42.42.42.42");
        $this->click("Remove"); // FIXME: remove which ban? :S
        $this->assert_no_text("42.42.42.42");

        $this->get_page('ip_ban/list?r_all=on'); // just test it doesn't crash for now

        # FIXME: test that the IP is actually banned
    }
}
