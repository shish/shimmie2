<?php
class UserPageTest extends ShimmieWebTestCase {
	function testUserPage() {
		$this->get_page('user');
		$this->assertTitle("Anonymous's Page");
		$this->assertNoText("Options");
		$this->assertNoText("More Options");

		$this->get_page('user/demo');
		$this->assertTitle("demo's Page");

		$this->get_page('user/MauMau');
		$this->assertTitle("No Such User");

		$this->log_in_as_user();
		// should be on the user page
		$this->assertTitle(USER_NAME+"'s Page");
		$this->assertText("Options");
		$this->assertNoText("More Options");
		$this->log_out();

		$this->log_in_as_admin();
		// should be on the user page
		$this->assertTitle(ADMIN_NAME+"'s Page");
		$this->assertText("Options");
		$this->assertText("More Options");
		$this->log_out();
	}
}
?>
