<?php
class IPBanTest extends WebTestCase {
	function testIPBan() {
        $this->get(TEST_BASE.'/ip_ban/list');
		$this->assertResponse(403);
		$this->assertTitle("Permission Denied");

        $this->get(TEST_BASE.'/user');
		$this->assertText("Login");
		$this->setField('user', ADMIN_NAME);
		$this->setField('pass', ADMIN_PASS);
		$this->click("Log In");

        $this->get(TEST_BASE.'/ip_ban/list');
		$this->assertNoText("42.42.42.42");
		$this->setField('ip', '42.42.42.42');
		$this->setField('reason', 'unit testing');
		$this->setField('end', '1 week');
		$this->click("Ban");
		
		$this->assertText("42.42.42.42");
		$this->click("Remove"); // FIXME: remove which ban? :S
		$this->assertNoText("42.42.42.42");

		$this->click('Log Out');
	}
}
?>

