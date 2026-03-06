<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class PolyfillsTest extends TestCase
{
    public function test_int_escape(): void
    {
        self::assertSame(0, int_escape(""));
        self::assertSame(1, int_escape("1"));
        self::assertSame(-1, int_escape("-1"));
        self::assertSame(-1, int_escape("-1.5"));
        self::assertSame(0, int_escape(null));
    }

    public function test_url_escape(): void
    {
        self::assertSame("%5E%5Co%2F%5E", url_escape("^\o/^"));
        self::assertSame("", url_escape(null));
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
        self::assertSame(5, clamp(0, 5, 10)); // too small
        self::assertSame(5, clamp(5, 5, 10)); // lower limit
        self::assertSame(7, clamp(7, 5, 10)); // ok
        self::assertSame(10, clamp(10, 5, 10)); // upper limit
        self::assertSame(10, clamp(15, 5, 10)); // too large
        self::assertSame(0, clamp(0, null, 10)); // no lower limit
        self::assertSame(10, clamp(10, 0, null)); // no upper limit
        self::assertSame(42, clamp(42, null, null)); // no limit
    }

    public function test_truncate(): void
    {
        self::assertSame("test words", truncate("test words", 10), "No truncation if string is short enough");
        self::assertSame("test...", truncate("test words", 9), "Truncate when string is too long");
        self::assertSame("test...", truncate("test words", 7), "Truncate to the same breakpoint");
        self::assertSame("te...", truncate("test words", 5), "Breakpoints past the limit don't matter");
        self::assertSame("o...", truncate("oneVeryLongWord", 4), "Hard-break if there are no breakpoints");
    }

    public function test_to_shorthand_int(): void
    {
        // 0-9 should have 1 decimal place, 10+ should have none
        self::assertSame("1.1GB", to_shorthand_int(1231231231));
        self::assertSame("10KB", to_shorthand_int(10240));
        self::assertSame("9.2KB", to_shorthand_int(9440));
        self::assertSame("2", to_shorthand_int(2));
    }

    public function test_parse_shorthand_int(): void
    {
        self::assertSame(null, parse_shorthand_int("foo"));
        self::assertSame(-1, parse_shorthand_int("-1"));
        self::assertSame(0, parse_shorthand_int("0"));
        self::assertSame(33554432, parse_shorthand_int("32M"));
        self::assertSame(44441, parse_shorthand_int("43.4KB"));
        self::assertSame(1231231231, parse_shorthand_int("1231231231"));
    }

    public function test_format_milliseconds(): void
    {
        self::assertSame("", format_milliseconds(5));
        self::assertSame("5s", format_milliseconds(5000));
        self::assertSame("1y 213d 16h 53m 20s", format_milliseconds(50000000000));
    }

    public function test_parse_to_milliseconds(): void
    {
        self::assertSame(10, parse_to_milliseconds("10"));
        self::assertSame(5000, parse_to_milliseconds("5s"));
        self::assertSame(50000000000, parse_to_milliseconds("1y 213d 16h 53m 20s"));
    }

    public function test_stringer(): void
    {
        self::assertSame(
            '["foo"=>"bar", "baz"=>[1, 2, 3], "qux"=>["a"=>"b"]]',
            stringer(["foo" => "bar", "baz" => [1,2,3], "qux" => ["a" => "b"]])
        );
    }
}
