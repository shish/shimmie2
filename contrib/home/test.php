<?php
class HomeTest extends ShimmieWebTestCase {
    function testHomePage() {
        $this->get_page('home');
        $this->assert_title('Shimmie');
        $this->assert_text('Shimmie');

		# FIXME: test search box
    }
}
?>
