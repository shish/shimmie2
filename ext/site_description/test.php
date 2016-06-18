<?php
class SiteDescriptionTest extends ShimmiePHPUnitTestCase {
	public function testSiteDescription() {
		global $config, $page;
		$config->set_string("site_description", "A Shimmie testbed");
		$this->get_page("post/list");
		$this->assertContains(
			'<meta name="description" content="A Shimmie testbed">',
			$page->get_all_html_headers()
		);
	}

	public function testSiteKeywords() {
		global $config, $page;
		$config->set_string("site_keywords", "foo,bar,baz");
		$this->get_page("post/list");
		$this->assertContains(
			'<meta name="keywords" content="foo,bar,baz">',
			$page->get_all_html_headers()
		);
	}
}

