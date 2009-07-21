<?php
class SiteDescriptionTest extends SCoreWebTestCase {
	function testSiteDescription() {
		$this->log_in_as_admin();
		$this->get_page('setup');
		$this->assertTitle("Shimmie Setup");
		$this->setField("_config_site_description", "A Shimmie testbed");
		$raw = $this->click("Save Settings");

		$header = '<meta name="description" content="A Shimmie testbed">';
		$this->assertTrue(strpos($raw, $header) > 0);

		$this->log_out();
	}
}
?>
