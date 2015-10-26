<?php
class PoolsTest extends ShimmiePHPUnitTestCase {
	public function testPools() {
		$this->get_page('pool/list');
		$this->assert_title("Pools");

		$this->get_page('pool/new');
		$this->assert_title("Error");

		$this->log_in_as_user();
		$this->get_page('pool/list');

		$this->markTestIncomplete();

		$this->click("Create Pool");
		$this->assert_title("Create Pool");
		$this->click("Create");
		$this->assert_title("Error");

		$this->get_page('pool/new');
		$this->assert_title("Create Pool");
		$this->set_field("title", "Test Pool Title");
		$this->set_field("description", "Test pool description");
		$this->click("Create");
		$this->assert_title("Pool: Test Pool Title");

		$this->log_out();


		$this->log_in_as_admin();

		$this->get_page('pool/list');
		$this->click("Test Pool Title");
		$this->assert_title("Pool: Test Pool Title");
		$this->click("Delete Pool");
		$this->assert_title("Pools");
		$this->assert_no_text("Test Pool Title");

		$this->log_out();
	}
}

