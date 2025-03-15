<?php

declare(strict_types=1);

namespace Shimmie2;

final class FourOhFourTest extends ShimmiePHPUnitTestCase
{
    public function test404Handler(): void
    {
        self::assertException(ObjectNotFound::class, function () {
            self::get_page('not/a/page');
        });
    }
}
