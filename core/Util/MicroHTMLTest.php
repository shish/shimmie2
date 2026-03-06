<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class MicroHTMLTest extends TestCase
{
    public function test_date(): void
    {
        self::assertSame(
            "<time datetime='2012-06-23T16:14:22+00:00'>June 23, 2012; 16:14</time>",
            (string)SHM_DATE("2012-06-23 16:14:22")
        );
    }
}
