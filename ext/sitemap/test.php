<?php
class XMLSitemapTest extends ShimmieWebTestCase {
	function testBasic() {
		# this will implicitly check that there are no
		# PHP-level error messages
		$this->get_page('sitemap.xml');
	}
}
?>
