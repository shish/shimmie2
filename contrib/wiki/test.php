<?php
class WikiTest extends SCoreWebTestCase {
	function testIndex() {
		$this->get_page("wiki");
		$this->assertTitle("Index");
		$this->assertText("This is a default page");
	}

	function testAccess() {
		foreach(array("anon", "user", "admin") as $user) {
			foreach(array(false, true) as $allowed) {
				// admin has no settings to set
				if($user != "admin") {
					$this->log_in_as_admin();
					$this->get_page("setup");
					$this->setField("_config_wiki_edit_$user", $allowed);
					$this->click("Save Settings");
					$this->log_out();
				}

				if($user == "user") {$this->log_in_as_user();}
				if($user == "admin") {$this->log_in_as_admin();}

				$this->get_page("wiki/test");
				$this->assertTitle("test");
				$this->assertText("This is a default page");
				if($allowed || $user == "admin") {
					$this->click("Edit");
					$this->assertText("Editor");
				}
				else {
					$this->click("Edit");
					$this->assertNoText("Editor");
				}

				if($user == "user" || $user == "admin") {$this->log_out();}
			}
		}
	}

	function testLock() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->setField("_config_wiki_Edit_anon", false);
		$this->setField("_config_wiki_Edit_user", true);
		$this->click("Save Settings");

		$this->get_page("wiki/test_locked");
		$this->assertTitle("test_locked");
		$this->assertText("This is a default page");
		$this->click("Edit");
		$this->setField("body", "test_locked content");
		$this->setField("lock", true);
		$this->click("Save");
		$this->log_out();

		$this->log_in_as_user();
		$this->get_page("wiki/test_locked");
		$this->assertTitle("test_locked");
		$this->assertText("test_locked content");
		$this->assertNoText("Edit");
		$this->log_out();

		$this->get_page("wiki/test_locked");
		$this->assertTitle("test_locked");
		$this->assertText("test_locked content");
		$this->assertNoText("Edit");

		$this->log_in_as_admin();
		$this->get_page("wiki/test_locked");
		$this->click("Delete All");
		$this->log_out();
	}

	function testDefault() {
		$this->log_in_as_admin();
		$this->get_page("wiki/wiki:default");
		$this->assertTitle("wiki:default");
		$this->assertText("This is a default page");
		$this->click("Edit");
		$this->setField("body", "Empty page! Fill it!");
		$this->click("Save");

		$this->get_page("wiki/something");
		$this->assertText("Empty page! Fill it!");

		$this->get_page("wiki/wiki:default");
		$this->click("Delete All");
		$this->log_out();
	}

	function testRevisions() {
		$this->log_in_as_admin();
		$this->get_page("wiki/test");
		$this->assertTitle("test");
		$this->assertText("This is a default page");
		$this->click("Edit");
		$this->setField("body", "Mooooo 1");
		$this->click("Save");
		$this->assertText("Mooooo 1");
		$this->assertText("Revision 1");
		$this->click("Edit");
		$this->setField("body", "Mooooo 2");
		$this->click("Save");
		$this->assertText("Mooooo 2");
		$this->assertText("Revision 2");
		$this->click("Delete This Version");
		$this->assertText("Mooooo 1");
		$this->assertText("Revision 1");
		$this->click("Delete All");
		$this->log_out();
	}
}
?>
