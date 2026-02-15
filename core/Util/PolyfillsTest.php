<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class PolyfillsTest extends TestCase
{
    public function test_int_escape(): void
    {
        self::assertEquals(0, int_escape(""));
        self::assertEquals(1, int_escape("1"));
        self::assertEquals(-1, int_escape("-1"));
        self::assertEquals(-1, int_escape("-1.5"));
        self::assertEquals(0, int_escape(null));
    }

    public function test_url_escape(): void
    {
        self::assertEquals("%5E%5Co%2F%5E", url_escape("^\o/^"));
        self::assertEquals("", url_escape(null));
    }

    public function test_bool_escape(): void
    {
        self::assertTrue(bool_escape("true"));
        self::assertFalse(bool_escape("false"));

        self::assertTrue(bool_escape("t"));
        self::assertFalse(bool_escape("f"));

        self::assertTrue(bool_escape("T"));
        self::assertFalse(bool_escape("F"));

        self::assertTrue(bool_escape("yes"));
        self::assertFalse(bool_escape("no"));

        self::assertTrue(bool_escape("yeS"));
        self::assertFalse(bool_escape("No"));

        self::assertTrue(bool_escape("on"));
        self::assertFalse(bool_escape("off"));

        self::assertTrue(bool_escape("1"));
        self::assertFalse(bool_escape("0"));
    }

    public function test_clamp(): void
    {
        self::assertEquals(5, clamp(0, 5, 10)); // too small
        self::assertEquals(5, clamp(5, 5, 10)); // lower limit
        self::assertEquals(7, clamp(7, 5, 10)); // ok
        self::assertEquals(10, clamp(10, 5, 10)); // upper limit
        self::assertEquals(10, clamp(15, 5, 10)); // too large
        self::assertEquals(0, clamp(0, null, 10)); // no lower limit
        self::assertEquals(10, clamp(10, 0, null)); // no upper limit
        self::assertEquals(42, clamp(42, null, null)); // no limit
    }

    public function test_truncate(): void
    {
        self::assertEquals("test words", truncate("test words", 10), "No truncation if string is short enough");
        self::assertEquals("test...", truncate("test words", 9), "Truncate when string is too long");
        self::assertEquals("test...", truncate("test words", 7), "Truncate to the same breakpoint");
        self::assertEquals("te...", truncate("test words", 5), "Breakpoints past the limit don't matter");
        self::assertEquals("o...", truncate("oneVeryLongWord", 4), "Hard-break if there are no breakpoints");
    }

    public function test_to_shorthand_int(): void
    {
        // 0-9 should have 1 decimal place, 10+ should have none
        self::assertEquals("1.1GB", to_shorthand_int(1231231231));
        self::assertEquals("10KB", to_shorthand_int(10240));
        self::assertEquals("9.2KB", to_shorthand_int(9440));
        self::assertEquals("2", to_shorthand_int(2));
    }

    public function test_parse_shorthand_int(): void
    {
        self::assertEquals(null, parse_shorthand_int("foo"));
        self::assertEquals(-1, parse_shorthand_int("-1"));
        self::assertEquals(33554432, parse_shorthand_int("32M"));
        self::assertEquals(44441, parse_shorthand_int("43.4KB"));
        self::assertEquals(1231231231, parse_shorthand_int("1231231231"));
    }

    public function test_format_milliseconds(): void
    {
        self::assertEquals("", format_milliseconds(5));
        self::assertEquals("5s", format_milliseconds(5000));
        self::assertEquals("1y 213d 16h 53m 20s", format_milliseconds(50000000000));
    }

    public function test_parse_to_milliseconds(): void
    {
        self::assertEquals(10, parse_to_milliseconds("10"));
        self::assertEquals(5000, parse_to_milliseconds("5s"));
        self::assertEquals(50000000000, parse_to_milliseconds("1y 213d 16h 53m 20s"));
    }

    public function test_stringer(): void
    {
        self::assertEquals(
            '["foo"=>"bar", "baz"=>[1, 2, 3], "qux"=>["a"=>"b"]]',
            stringer(["foo" => "bar", "baz" => [1,2,3], "qux" => ["a" => "b"]])
        );
    }
}
