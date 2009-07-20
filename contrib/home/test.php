<?php
class HomeTest extends ShimmieWebTestCase {
    function testHomePage() {
        $this->get_page('home');
        $this->assertTitle('Shimmie');
        $this->assertText('Shimmie');

		# FIXME: test search box
    }
}
?>
