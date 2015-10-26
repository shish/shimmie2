<?php
class TipsTest extends ShimmiePHPUnitTestCase {
	public function setUp() {
		parent::setUp();

		$this->log_in_as_admin();
		$this->get_page("tips/list");

		$this->markTestIncomplete();

		// get rid of the default data if it's there
		if(strpos($raw, "Delete")) {
			$this->click("Delete");
		}
		$this->log_out();
	}

	public function testImageless() {
		$this->log_in_as_admin();

		$this->get_page("tips/list");
		$this->assert_title("Tips List");

		$this->markTestIncomplete();

		$this->set_field("image", "");
		$this->set_field("text", "an imageless tip");
		$this->click("Submit");
		$this->assert_title("Tips List");

		$this->get_page("post/list");
		$this->assert_text("an imageless tip");

		$this->get_page("tips/list");
		$this->click("Delete");

		$this->log_out();
	}

	public function testImaged() {
		$this->log_in_as_admin();

		$this->get_page("tips/list");
		$this->assert_title("Tips List");

		$this->markTestIncomplete();

		$this->set_field("image", "coins.png");
		$this->set_field("text", "an imaged tip");
		$this->click("Submit");
		$this->assert_title("Tips List");

		$this->get_page("post/list");
		$this->assert_text("an imaged tip");

		$this->get_page("tips/list");
		$this->click("Delete");

		$this->log_out();
	}

	public function testDisabled() {
		$this->log_in_as_admin();

		$this->get_page("tips/list");
		$this->assert_title("Tips List");

		$this->markTestIncomplete();

		$this->set_field("image", "coins.png");
		$this->set_field("text", "an imaged tip");
		$this->click("Submit");
		$this->click("Yes");
		$this->assert_title("Tips List");

		$this->get_page("post/list");
		$this->assert_no_text("an imaged tip");

		$this->get_page("tips/list");
		$this->click("Delete");

		$this->log_out();
	}
}

