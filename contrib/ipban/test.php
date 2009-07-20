<?php
class IPBanTest extends ShimmieWebTestCase {
	function testIPBan() {
        $this->get_page('ip_ban/list');
		$this->assertResponse(403);
		$this->assertTitle("Permission Denied");

        $this->log_in_as_admin();

        $this->get_page('ip_ban/list');
		$this->assertNoText("42.42.42.42");
		$this->setField('ip', '42.42.42.42');
		$this->setField('reason', 'unit testing');
		$this->setField('end', '1 week');
		$this->click("Ban");

		$this->assertText("42.42.42.42");
		$this->click("Remove"); // FIXME: remove which ban? :S
		$this->assertNoText("42.42.42.42");

		$this->log_out();

		# FIXME: test that the IP is actually banned
	}
}
?>

