<?php declare(strict_types=1);
class OekakiTest extends ShimmiePHPUnitTestCase
{
    public function testLog()
    {
        $this->log_in_as_user();
        $this->get_page("oekaki/create");
    }
}
