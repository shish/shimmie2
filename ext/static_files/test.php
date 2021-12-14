<?php

declare(strict_types=1);
class StaticFilesTest extends ShimmiePHPUnitTestCase
{
    public function testStaticHandler()
    {
        $this->get_page('favicon.ico');
        $this->assert_response(200);
    }
}
