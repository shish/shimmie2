<?php
class XMLSitemapTest extends ShimmieWebTestCase {
	function testBasic() {
		# this will implicitly check that there are no
		# PHP-level error messages
		$this->get_page('sitemap.xml');
	}

	function testImage() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");
		$this->log_out();

		$this->get_page('sitemap.xml');

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
