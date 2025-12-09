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

    /**
     * - MySQL and Postgres use '\' for sql escaping by default,
     *   and don't accept ESCAPE
     * - SQLite requires the user to add an explicit "ESCAPE '\'" on
     *   every LIKE statement
     */
    public function test_like_escape(): void
    {
        Ctx::$database->execute("INSERT INTO tags(tag) VALUES (:val)", ["val" => "abcd1"]);
        Ctx::$database->execute("INSERT INTO tags(tag) VALUES (:val)", ["val" => "ABCD2"]);
        Ctx::$database->execute("INSERT INTO tags(tag) VALUES (:val)", ["val" => "a_cd3"]);

        // with regular SQL there's no one query that works on all DBs
        self::assertEquals(
            ["a_cd3"],
            Ctx::$database->get_col(match(Ctx::$database->get_driver_id()) {
                DatabaseDriverID::SQLITE => "SELECT tag FROM tags WHERE tag LIKE :pattern ESCAPE '\\'",
                DatabaseDriverID::MYSQL => "SELECT tag FROM tags WHERE tag LIKE :pattern",
                DatabaseDriverID::PGSQL => "SELECT tag FROM tags WHERE tag LIKE :pattern",
            }, ["pattern" => "a\\_%"]),
            "LIKE escaping is weird"
        );

        // with SCORE_ILIKE, we can use the same query on all DBs
        self::assertEquals(
            ["a_cd3"],
            Ctx::$database->get_col("SELECT tag FROM tags WHERE SCORE_ILIKE(tag, :pattern)", ["pattern" => "a\\_%"]),
            "SCORE_ILIKE consistently uses backslash as an escape character"
        );
    }

    /**
     * - SQLITE is case-insensitive for ASCII
     * - MYSQL is case-insensitive for ASCII
     * - PGSQL is case-sensitive for ASCII, but has ILIKE
     */
    public function test_like_case_ascii(): void
    {
        Ctx::$database->execute("INSERT INTO tags(tag) VALUES (:val)", ["val" => "abcd1"]);
        Ctx::$database->execute("INSERT INTO tags(tag) VALUES (:val)", ["val" => "ABCD2"]);
        Ctx::$database->execute("INSERT INTO tags(tag) VALUES (:val)", ["val" => "a_cd3"]);

        // With regular SQL lower(x) == lower(y) works across DBs, but that
        // requires running lower() a lot, and on mysql that's not needed at all
        self::assertEquals(
            ["abcd1", "ABCD2"],
            Ctx::$database->get_col("SELECT tag FROM tags WHERE lower(tag) LIKE lower(:pattern)", ["pattern" => "ab%"]),
            "LIKE case-sensitivity is weird"
        );

        // Other than mass-lower(), we need to use dabase-specific syntax
        self::assertEquals(
            ["abcd1", "ABCD2"],
            Ctx::$database->get_col(match(Ctx::$database->get_driver_id()) {
                DatabaseDriverID::SQLITE => "SELECT tag FROM tags WHERE tag LIKE :pattern",
                DatabaseDriverID::MYSQL => "SELECT tag FROM tags WHERE tag LIKE :pattern",
                DatabaseDriverID::PGSQL => "SELECT tag FROM tags WHERE tag ILIKE :pattern",
            }, ["pattern" => "ab%"]),
            "LIKE case-sensitivity is weird"
        );

        // With SCORE_ILIKE, we can use the same query on all DBs
        self::assertEquals(
            ["abcd1", "ABCD2"],
            Ctx::$database->get_col("SELECT tag FROM tags WHERE SCORE_ILIKE(tag, :pattern)", ["pattern" => "ab%"]),
            "SCORE_ILIKE is consistently case-insensitive for ascii"
        );
    }

    /**
     * - SQLITE is case-sensitive for UTF-8
     * - MYSQL is case-insensitive for UTF-8
     * - PGSQL is case-sensitive for UTF-8, but has ILIKE
     */
    public function test_like_case_utf8(): void
    {
        Ctx::$database->execute("INSERT INTO tags(tag) VALUES (:val)", ["val" => "советских1"]);
        Ctx::$database->execute("INSERT INTO tags(tag) VALUES (:val)", ["val" => "Советских2"]);
        Ctx::$database->execute("INSERT INTO tags(tag) VALUES (:val)", ["val" => "С_ветских3"]);

        // With regular SQL lower(x) == lower(y) works across DBs, but that
        // requires running lower() a lot, and on mysql that's not needed at all
        self::assertEquals(
            ["советских1", "Советских2"],
            Ctx::$database->get_col("SELECT tag FROM tags WHERE lower(tag) LIKE lower(:pattern)", ["pattern" => "со%"]),
            "LIKE case-sensitivity is weird"
        );

        // Other than mass-lower(), we need to use dabase-specific syntax
        self::assertEquals(
            ["советских1", "Советских2"],
            Ctx::$database->get_col(match(Ctx::$database->get_driver_id()) {
                DatabaseDriverID::SQLITE => "SELECT tag FROM tags WHERE lower(tag) LIKE lower(:pattern)",
                DatabaseDriverID::MYSQL => "SELECT tag FROM tags WHERE tag LIKE :pattern",
                DatabaseDriverID::PGSQL => "SELECT tag FROM tags WHERE tag ILIKE :pattern",
            }, ["pattern" => "со%"]),
            "LIKE case-sensitivity is weird"
        );

        // With SCORE_ILIKE, we can use the same query on all DBs
        self::assertEquals(
            ["советских1", "Советских2"],
            Ctx::$database->get_col("SELECT tag FROM tags WHERE SCORE_ILIKE(tag, :pattern)", ["pattern" => "со%"]),
            "SCORE_ILIKE is consistently case-insensitive for utf-8"
        );
    }

    public function test_booleans(): void
    {
        self::assertTrue(bool_escape(Ctx::$database->get_one("SELECT :true", ["true" => true])), "True parameter");
        self::assertFalse(bool_escape(Ctx::$database->get_one("SELECT :false", ["false" => false])), "False parameter");
        self::assertTrue(bool_escape(Ctx::$database->get_one("SELECT TRUE")), "TRUE literal");
        self::assertFalse(bool_escape(Ctx::$database->get_one("SELECT FALSE")), "FALSE literal");

        self::assertTrue(bool_escape(Ctx::$database->get_one("SELECT TRUE = :true", ["true" => true])), "TRUE comparison");
        self::assertTrue(bool_escape(Ctx::$database->get_one("SELECT FALSE = :false", ["false" => false])), "FALSE comparison");
    }
}
