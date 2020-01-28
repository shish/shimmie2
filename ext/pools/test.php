<?php declare(strict_types=1);
class PoolsTest extends ShimmiePHPUnitTestCase
{
    public function testAnon() {
        $this->get_page('pool/list');
        $this->assert_title("Pools");

        $this->get_page('pool/new');
        $this->assert_title("Error");
    }
}
