<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Because various SQL engines have wildly different support for
 * various SQL features, and sometimes they are silently incompatible,
 * here's a test suite to ensure that certain features give predictable
 * results on all engines and thus can be considered safe to use.
 */
class SQLTest extends ShimmiePHPUnitTestCase
{
    public function testConcatPipes(): void
    {
        global $database;
        $this->assertEquals(
            "foobar",
            $database->get_one("SELECT 'foo' || 'bar'")
        );
    }
}
