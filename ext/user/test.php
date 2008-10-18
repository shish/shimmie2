<?php
class UserPageTest extends WebTestCase {
	function testUserPage() {
        $this->get(TEST_BASE.'/user');
        $this->assertTitle("Anonymous's Page");
		$this->assertNoText("Options");
		$this->assertNoText("More Options");

        $this->get(TEST_BASE.'/user/Shish');
        $this->assertTitle("Shish's Page");

        $this->get(TEST_BASE.'/user/MauMau');
        $this->assertTitle("No Such User");

		$this->assertText("Login");
		$this->setField('user', USER_NAME);
		$this->setField('pass', USER_PASS);
		$this->click("Log In");
		// should be on the user page
        $this->assertTitle("test's Page");
		$this->assertText("Options");
		$this->assertNoText("More Options");
		$this->click('Log Out');

		$this->assertText("Login");
		$this->setField('user', ADMIN_NAME);
		$this->setField('pass', ADMIN_PASS);
		$this->click("Log In");
		// should be on the user page
        $this->assertTitle(ADMIN_NAME+"'s Page");
		$this->assertText("Options");
		$this->assertText("More Options");
		$this->click('Log Out');
	}
}
?>
