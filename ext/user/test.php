<?php
class UserPageTest extends ShimmiePHPUnitTestCase {
	public function testUserPage() {
		$this->get_page('user');
		$this->assert_title("Not Logged In");
		$this->assert_no_text("Options");
		$this->assert_no_text("More Options");

		$this->get_page('user/demo');
		$this->assert_title("demo's Page");
		$this->assert_text("Joined:");

		$this->get_page('user/MauMau');
		$this->assert_title("No Such User");

		$this->log_in_as_user();
		// should be on the user page
		$this->get_page('user/test');
		$this->assert_title("test's Page");
		$this->assert_text("Options");
		// FIXME: check class
		//$this->assert_no_text("Admin:");
		$this->log_out();

		$this->log_in_as_admin();
		// should be on the user page
		$this->get_page('user/demo');
		$this->assert_title("demo's Page");
		$this->assert_text("Options");
		// FIXME: check class
		//$this->assert_text("Admin:");
		$this->log_out();

		# FIXME: test user creation
		# FIXME: test adminifying
		# FIXME: test password reset

		$this->get_page('user_admin/list');
		$this->assert_text("demo");
	}
}
