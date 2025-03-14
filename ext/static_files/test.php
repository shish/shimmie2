<?php

declare(strict_types=1);

namespace Shimmie2;

class StaticFilesTest extends ShimmiePHPUnitTestCase
{
    public function testStaticHandler(): void
    {
        $this->get_page('favicon.ico');
        self::assert_response(200);
    }
}
