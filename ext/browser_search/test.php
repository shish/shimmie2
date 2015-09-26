<?php
class BrowserSearchTest extends ShimmiePHPUnitTestCase {
	public function testBasic() {
		$this->get_page("browser_search/please_dont_use_this_tag_as_it_would_break_stuff__search.xml");
		$this->get_page("browser_search/test");
	}
}

