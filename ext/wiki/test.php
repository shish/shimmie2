<?php
class WikiTest extends ShimmiePHPUnitTestCase {
	public function testIndex() {
		$this->get_page("wiki");
		$this->assert_title("Index");
		$this->assert_text("This is a default page");
	}

	public function testAccess() {
		$this->markTestIncomplete();

		global $config;
		foreach(array("anon", "user", "admin") as $user) {
			foreach(array(false, true) as $allowed) {
				// admin has no settings to set
				if($user != "admin") {
					$config->set_bool("wiki_edit_$user", $allowed);
				}

				if($user == "user") {$this->log_in_as_user();}
				if($user == "admin") {$this->log_in_as_admin();}

				$this->get_page("wiki/test");
				$this->assert_title("test");
				$this->assert_text("This is a default page");

				if($allowed || $user == "admin") {
					$this->get_page("wiki/test", array('edit'=>'on'));
					$this->assert_text("Editor");
				}
				else {
					$this->get_page("wiki/test", array('edit'=>'on'));
					$this->assert_no_text("Editor");
				}

				if($user == "user" || $user == "admin") {
					$this->log_out();
				}
			}
		}
	}

	public function testLock() {
		$this->markTestIncomplete();

		global $config;
		$config->set_bool("wiki_edit_anon", true);
		$config->set_bool("wiki_edit_user", false);

		$this->log_in_as_admin();

		$this->get_page("wiki/test_locked");
		$this->assert_title("test_locked");
		$this->assert_text("This is a default page");
		$this->click("Edit");
		$this->set_field("body", "test_locked content");
		$this->set_field("lock", true);
		$this->click("Save");
		$this->log_out();

		$this->log_in_as_user();
		$this->get_page("wiki/test_locked");
		$this->assert_title("test_locked");
		$this->assert_text("test_locked content");
		$this->assert_no_text("Edit");
		$this->log_out();

		$this->get_page("wiki/test_locked");
		$this->assert_title("test_locked");
		$this->assert_text("test_locked content");
		$this->assert_no_text("Edit");

		$this->log_in_as_admin();
		$this->get_page("wiki/test_locked");
		$this->click("Delete All");
		$this->log_out();
	}

	public function testDefault() {
		$this->markTestIncomplete();

		$this->log_in_as_admin();
		$this->get_page("wiki/wiki:default");
		$this->assert_title("wiki:default");
		$this->assert_text("This is a default page");
		$this->click("Edit");
		$this->set_field("body", "Empty page! Fill it!");
		$this->click("Save");

		$this->get_page("wiki/something");
		$this->assert_text("Empty page! Fill it!");

		$this->get_page("wiki/wiki:default");
		$this->click("Delete All");
		$this->log_out();
	}

	public function testRevisions() {
		$this->markTestIncomplete();

		$this->log_in_as_admin();
		$this->get_page("wiki/test");
		$this->assert_title("test");
		$this->assert_text("This is a default page");
		$this->click("Edit");
		$this->set_field("body", "Mooooo 1");
		$this->click("Save");
		$this->assert_text("Mooooo 1");
		$this->assert_text("Revision 1");
		$this->click("Edit");
		$this->set_field("body", "Mooooo 2");
		$this->click("Save");
		$this->assert_text("Mooooo 2");
		$this->assert_text("Revision 2");
		$this->click("Delete This Version");
		$this->assert_text("Mooooo 1");
		$this->assert_text("Revision 1");
		$this->click("Delete All");
		$this->log_out();
	}
}

