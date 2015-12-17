<?php
class BulkAddTest extends ShimmiePHPUnitTestCase {
	public function testBulkAdd() {
		$this->log_in_as_admin();

		$this->get_page('admin');
		$this->assert_title("Admin Tools");

		$bae = new BulkAddEvent('asdf');
		send_event($bae);
		$this->assertContains("Error, asdf is not a readable directory",
			$bae->results, implode("\n", $bae->results));

		// FIXME: have BAE return a list of successes as well as errors?
		$this->markTestIncomplete();

		$this->get_page('admin');
		$this->assert_title("Admin Tools");
		send_event(new BulkAddEvent('tests'));

		# FIXME: test that the output here makes sense, no "adding foo.php ... ok"

		$this->get_page("post/list/hash=17fc89f372ed3636e28bd25cc7f3bac1/1");
		$this->assert_title(new PatternExpectation("/^Image \d+: data/"));
		$this->click("Delete");

		$this->get_page("post/list/hash=feb01bab5698a11dd87416724c7a89e3/1");
		$this->assert_title(new PatternExpectation("/^Image \d+: data/"));
		$this->click("Delete");

		$this->get_page("post/list/hash=e106ea2983e1b77f11e00c0c54e53805/1");
		$this->assert_title(new PatternExpectation("/^Image \d+: data/"));
		$this->click("Delete");

		$this->log_out();
	}
}
