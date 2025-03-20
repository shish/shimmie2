<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Attributes\Depends;

/**
 * Because various SQL engines have wildly different support for
 * various SQL features, and sometimes they are silently incompatible,
 * here's a test suite to ensure that certain features give predictable
 * results on all engines and thus can be considered safe to use.
 */
final class SQLTest extends ShimmiePHPUnitTestCase
{
    public function testConcatPipes(): void
    {
        self::assertEquals(
            "foobar",
            Ctx::$database->get_one("SELECT 'foo' || 'bar'")
        );
    }

    public function testNow(): void
    {
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(\.\d+)?(\+\d+)?$/',
            Ctx::$database->get_one("SELECT now()")
        );
    }

    public function testLog(): void
    {
        self::assertEqualsWithDelta(1.0, Ctx::$database->get_one("SELECT log(10, 10)"), 0.01);
        // Some DBs default to log(10, n) and some to log(E, n), so we can't use this'
        // self::assertEqualsWithDelta(2.3, $database->get_one("SELECT log(10)"), 0.01);
        self::assertEqualsWithDelta(2.3, Ctx::$database->get_one("SELECT ln(10)"), 0.01);
        self::assertEqualsWithDelta(3.0, Ctx::$database->get_one("SELECT log(2, 8)"), 0.01);
    }

    /**
     * confirm that strtolower does not work with Cyrillic, but mb_strtolower
     * does - if this test fails, then the database test below makes no sense
     */
    public function test_cyrillic_php_lowercase(): void
    {
        self::assertNotEquals("советских", strtolower("Советских"), "strtolower");
        self::assertEquals("советских", mb_strtolower("Советских"), "mb_strtolower");
    }

    /**
     * LOWER is UTF-8 aware in MySQL and PostgreSQL, but is ASCII-only in
     * SQLite... buuuuut SQLite does allow us to define our own functions,
     * and we can override the LOWER function with a call to mb_strtolower.
     * This test ensures that we now get consistent results across all DBs.
     */
    #[Depends("test_cyrillic_php_lowercase")]
    public function test_cyrillic_database_lowercase(): void
    {
        self::assertEquals("советских", Ctx::$database->get_one("SELECT LOWER('Советских')"), "LOWER");
    }
}
