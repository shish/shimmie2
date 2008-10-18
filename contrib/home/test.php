<?php
class HomeTest extends WebTestCase {
    function testHomePage() {
        $this->get(TEST_BASE.'/home');
        $this->assertTitle('Shimmie Testbed');
        $this->assertText('Shimmie Testbed');
    }
}
?>
