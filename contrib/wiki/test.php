<?php
class WikiTest extends SCoreWebTestCase {
	function testIndex() {
		$this->get_page("wiki");
		$this->assert_title("Index");
		$this->assert_text("This is a default page");
	}

	function testAccess() {
		foreach(array("anon", "user", "admin") as $user) {
			foreach(array(false, true) as $allowed) {
				// admin has no settings to set
				if($user != "admin") {
					$this->log_in_as_admin();
					$this->get_page("setup");
					$this->set_field("_config_wiki_edit_$user", $allowed);
					$this->click("Save Settings");
					$this->log_out();
				}

				if($user == "user") {$this->log_in_as_user();}
				if($user == "admin") {$this->log_in_as_admin();}

				$this->get_page("wiki/test");
				$this->assert_title("test");
				$this->assert_text("This is a default page");
				if($allowed || $user == "admin") {
					$this->click("Edit");
					$this->assert_text("Editor");
				}
				else {
					$this->click("Edit");
					$this->assert_no_text("Editor");
				}

				if($user == "user" || $user == "admin") {$this->log_out();}
			}
		}
	}

	function testLock() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->set_field("_config_wiki_edit_anon", false);
		$this->set_field("_config_wiki_edit_user", true);
		$this->click("Save Settings");

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

	function testDefault() {
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

	function testRevisions() {
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
?>
