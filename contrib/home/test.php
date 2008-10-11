<?php
class HomeTest extends WebTestCase {
    function testHomePage() {
        $this->get('http://shimmie.shishnet.org/v2/home');
        $this->assertTitle('Shimmie Testbed');
        $this->assertText('Shimmie Testbed');
    }
}
?>
