<?php

declare(strict_types=1);

namespace Shimmie2;

final class StaticFilesTest extends ShimmiePHPUnitTestCase
{
    public function testStaticHandler(): void
    {
        self::get_page('favicon.ico');
        self::assert_response(200);
    }
}
