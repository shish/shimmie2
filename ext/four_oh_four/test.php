<?php

declare(strict_types=1);

namespace Shimmie2;

class FourOhFourTest extends ShimmiePHPUnitTestCase
{
    public function test404Handler(): void
    {
        $this->assertException(ObjectNotFound::class, function () {
            $this->get_page('not/a/page');
        });
    }
}
