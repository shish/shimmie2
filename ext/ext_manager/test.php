<?php
class ExtManagerTest extends WebTestCase {
	function testAuth() {
        $this->get(TEST_BASE.'/ext_manager');
		$this->assertResponse(403);
		$this->assertTitle("Permission Denied");

		$this->assertText("Login");
		$this->setField('user', USER_NAME);
		$this->setField('pass', USER_PASS);
		$this->click("Log In");
        $this->get(TEST_BASE.'/ext_manager');
		$this->assertResponse(403);
		$this->assertTitle("Permission Denied");
		$this->click('Log Out');

		$this->assertText("Login");
		$this->setField('user', ADMIN_NAME);
		$this->setField('pass', ADMIN_PASS);
		$this->click("Log In");
        $this->get(TEST_BASE.'/ext_manager');
		$this->assertTitle("Extensions");
		$this->assertText("SimpleTest integration");
		$this->click('Log Out');
	}
}
?>
