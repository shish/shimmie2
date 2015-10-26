<?php
class HomeTest extends ShimmiePHPUnitTestCase {
	public function testHomePage() {
		$this->get_page('home');

		// FIXME: this page doesn't use blocks; need assert_data_contains
		//$this->assert_title('Shimmie');
		//$this->assert_text('Shimmie');

		# FIXME: test search box
	}
}
