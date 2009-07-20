<?php
class SiteDescriptionTest extends ShimmieWebTestCase {
	function testSiteDescription() {
		$this->log_in_as_admin();
		$this->get_page('setup');
		$this->assertTitle("Shimmie Setup");
		$this->setField("_config_site_description", "A Shimmie testbed");
		$this->click("Save Settings");

		$raw_headers = $this->getBrowser()->getHeaders();
		$header = '<meta name="description" content="A Shimmie testbed">';
		$this->assertTrue(strpos($raw_headers, $header) > 0);

		$this->log_out();
	}
}
?>
