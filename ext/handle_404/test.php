<?php
class Handle404Test extends ShimmiePHPUnitTestCase {
	public function test404Handler() {
		$this->get_page('not/a/page');
		// most descriptive error first
		$this->assert_text("No handler could be found for the page 'not/a/page'");
		$this->assert_title('404');
		$this->assert_response(404);

		$this->get_page('favicon.ico');
		$this->assert_response(200);
	}
}

