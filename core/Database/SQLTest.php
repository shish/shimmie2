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
        // Some DBs default to log(10, n) and some to log(E, n), so we can't use this'
        // $this->assertEqualsWithDelta(2.3, $database->get_one("SELECT log(10)"), 0.01);
        $this->assertEqualsWithDelta(2.3, $database->get_one("SELECT ln(10)"), 0.01);
        $this->assertEqualsWithDelta(3.0, $database->get_one("SELECT log(2, 8)"), 0.01);
    }

    public function test_cyrillic_php_lowercase(): void
    {
        // confirm that strtolower does not work with Cyrillic, but mb_strtolower does
        $this->assertNotEquals("советских", strtolower("Советских"), "strtolower");
        $this->assertEquals("советских", mb_strtolower("Советских"), "mb_strtolower");
    }

    public function test_cyrillic_database_lowercase(): void
    {
        global $database;
        $this->assertEquals("советских", $database->get_one("SELECT LOWER('Советских')"), "LOWER");
    }
}
