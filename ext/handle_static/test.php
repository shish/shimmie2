<?php
class HandleStaticTest extends ShimmiePHPUnitTestCase {
	public function testStaticHandler() {
		$this->get_page('favicon.ico');
		$this->assert_response(200);
	}
}

