<?php
class UserPageTest extends SCoreWebTestCase {
	function testUserPage() {
		$this->get_page('user');
		$this->assert_title("Not Logged In");
		$this->assert_no_text("Options");
		$this->assert_no_text("More Options");

		$this->get_page('user/demo');
		$this->assert_title("demo's Page");
		$this->assert_text("Join date:");

		$this->get_page('user/MauMau');
		$this->assert_title("No Such User");

		$this->log_in_as_user();
		// should be on the user page
		$this->assert_title(USER_NAME+"'s Page");
		$this->assert_text("Options");
		$this->assert_no_text("Admin:");
		$this->log_out();

		$this->log_in_as_admin();
		// should be on the user page
		$this->assert_title(ADMIN_NAME+"'s Page");
		$this->assert_text("Options");
		$this->assert_text("Admin:");
		$this->log_out();

		# FIXME: test user creation
		# FIXME: test adminifying
		# FIXME: test password reset

		$this->get_page('user/list');
		$this->assert_text("demo");
	}
}
?>
