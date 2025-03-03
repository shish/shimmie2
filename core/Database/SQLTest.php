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

    public function testNow(): void
    {
        global $database;
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(\.\d+)?(\+\d+)?$/',
            $database->get_one("SELECT now()")
        );
    }

    public function testLog(): void
    {
        global $database;
        $this->assertEqualsWithDelta(1.0, $database->get_one("SELECT log(10, 10)"), 0.01);
        $this->assertEqualsWithDelta(2.3, $database->get_one("SELECT log(10)"), 0.01);
        $this->assertEqualsWithDelta(2.3, $database->get_one("SELECT ln(10)"), 0.01);
        $this->assertEqualsWithDelta(0.3, $database->get_one("SELECT log(10, 2)"), 0.01);
    }
}
