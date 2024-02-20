<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

require_once "core/polyfills.php";

class PolyfillsTest extends TestCase
{
    public function test_html_escape(): void
    {
        $this->assertEquals(
            "Foo &amp; &lt;main&gt;",
            html_escape("Foo & <main>")
        );

        $this->assertEquals(
            "Foo & <main>",
            html_unescape("Foo &amp; &lt;main&gt;")
        );

        $x = "Foo &amp; &lt;waffles&gt;";
        $this->assertEquals(html_escape(html_unescape($x)), $x);
    }

    public function test_int_escape(): void
    {
        $this->assertEquals(0, int_escape(""));
        $this->assertEquals(1, int_escape("1"));
        $this->assertEquals(-1, int_escape("-1"));
        $this->assertEquals(-1, int_escape("-1.5"));
        $this->assertEquals(0, int_escape(null));
    }

    public function test_url_escape(): void
    {
        $this->assertEquals("%5E%5Co%2F%5E", url_escape("^\o/^"));
        $this->assertEquals("", url_escape(null));
    }

    public function test_bool_escape(): void
    {
        $this->assertTrue(bool_escape(true));
        $this->assertFalse(bool_escape(false));

        $this->assertTrue(bool_escape("true"));
        $this->assertFalse(bool_escape("false"));

        $this->assertTrue(bool_escape("t"));
        $this->assertFalse(bool_escape("f"));

        $this->assertTrue(bool_escape("T"));
        $this->assertFalse(bool_escape("F"));

        $this->assertTrue(bool_escape("yes"));
        $this->assertFalse(bool_escape("no"));

        $this->assertTrue(bool_escape("Yes"));
        $this->assertFalse(bool_escape("No"));

        $this->assertTrue(bool_escape("on"));
        $this->assertFalse(bool_escape("off"));

        $this->assertTrue(bool_escape(1));
        $this->assertFalse(bool_escape(0));

        $this->assertTrue(bool_escape("1"));
        $this->assertFalse(bool_escape("0"));
    }

    public function test_clamp(): void
    {
        $this->assertEquals(5, clamp(0, 5, 10)); // too small
        $this->assertEquals(5, clamp(5, 5, 10)); // lower limit
        $this->assertEquals(7, clamp(7, 5, 10)); // ok
        $this->assertEquals(10, clamp(10, 5, 10)); // upper limit
        $this->assertEquals(10, clamp(15, 5, 10)); // too large
        $this->assertEquals(0, clamp(0, null, 10)); // no lower limit
        $this->assertEquals(10, clamp(10, 0, null)); // no upper limit
        $this->assertEquals(42, clamp(42, null, null)); // no limit
    }

    public function test_truncate(): void
    {
        $this->assertEquals("test words", truncate("test words", 10), "No truncation if string is short enough");
        $this->assertEquals("test...", truncate("test words", 9), "Truncate when string is too long");
        $this->assertEquals("test...", truncate("test words", 7), "Truncate to the same breakpoint");
        $this->assertEquals("te...", truncate("test words", 5), "Breakpoints past the limit don't matter");
        $this->assertEquals("o...", truncate("oneVeryLongWord", 4), "Hard-break if there are no breakpoints");
    }

    public function test_to_shorthand_int(): void
    {
        // 0-9 should have 1 decimal place, 10+ should have none
        $this->assertEquals("1.1GB", to_shorthand_int(1231231231));
        $this->assertEquals("10KB", to_shorthand_int(10240));
        $this->assertEquals("9.2KB", to_shorthand_int(9440));
        $this->assertEquals("2", to_shorthand_int(2));
    }

    public function test_parse_shorthand_int(): void
    {
        $this->assertEquals(-1, parse_shorthand_int("foo"));
        $this->assertEquals(33554432, parse_shorthand_int("32M"));
        $this->assertEquals(44441, parse_shorthand_int("43.4KB"));
        $this->assertEquals(1231231231, parse_shorthand_int("1231231231"));
    }

    public function test_format_milliseconds(): void
    {
        $this->assertEquals("", format_milliseconds(5));
        $this->assertEquals("5s", format_milliseconds(5000));
        $this->assertEquals("1y 213d 16h 53m 20s", format_milliseconds(50000000000));
    }

    public function test_parse_to_milliseconds(): void
    {
        $this->assertEquals(10, parse_to_milliseconds("10"));
        $this->assertEquals(5000, parse_to_milliseconds("5s"));
        $this->assertEquals(50000000000, parse_to_milliseconds("1y 213d 16h 53m 20s"));
    }

    public function test_autodate(): void
    {
        $this->assertEquals(
            "<time datetime='2012-06-23T16:14:22+00:00'>June 23, 2012; 16:14</time>",
            autodate("2012-06-23 16:14:22")
        );
    }

    public function test_validate_input(): void
    {
        $_POST = [
            "foo" => " bar ",
            "to_null" => "  ",
            "num" => "42",
        ];
        $this->assertEquals(
            ["foo" => "bar"],
            validate_input(["foo" => "string,trim,lower"])
        );
        //$this->assertEquals(
        //    ["to_null"=>null],
        //    validate_input(["to_null"=>"string,trim,nullify"])
        //);
        $this->assertEquals(
            ["num" => 42],
            validate_input(["num" => "int"])
        );
    }

    public function test_sanitize_path(): void
    {
        $this->assertEquals(
            "one",
            sanitize_path("one")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            sanitize_path("one\\two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            sanitize_path("one/two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            sanitize_path("one\\\\two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            sanitize_path("one//two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            sanitize_path("one\\\\\\two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            sanitize_path("one///two")
        );

        $this->assertEquals(
            DIRECTORY_SEPARATOR."one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR,
            sanitize_path("\\/one/\\/\\/two\\/")
        );
    }

    public function test_join_path(): void
    {
        $this->assertEquals(
            "one",
            join_path("one")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two",
            join_path("one", "two")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR."three",
            join_path("one", "two", "three")
        );

        $this->assertEquals(
            "one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR."three",
            join_path("one/two", "three")
        );

        $this->assertEquals(
            DIRECTORY_SEPARATOR."one".DIRECTORY_SEPARATOR."two".DIRECTORY_SEPARATOR."three".DIRECTORY_SEPARATOR,
            join_path("\\/////\\\\one/\///"."\\//two\/\\//\\//", "//\/\\\/three/\\/\/")
        );
    }

    public function test_stringer(): void
    {
        $this->assertEquals(
            '["foo"=>"bar", "baz"=>[1, 2, 3], "qux"=>["a"=>"b"]]',
            stringer(["foo" => "bar", "baz" => [1,2,3], "qux" => ["a" => "b"]])
        );
    }

    public function test_ip_in_range(): void
    {
        $this->assertTrue(ip_in_range("1.2.3.4", "1.2.0.0/16"));
        $this->assertFalse(ip_in_range("4.3.2.1", "1.2.0.0/16"));

        // A single IP should be interpreted as a /32
        $this->assertTrue(ip_in_range("1.2.3.4", "1.2.3.4"));
    }

    public function test_deltree(): void
    {
        $tmp = sys_get_temp_dir();
        $dir = "$tmp/test_deltree";
        mkdir($dir);
        file_put_contents("$dir/foo", "bar");
        mkdir("$dir/baz");
        file_put_contents("$dir/baz/.qux", "quux");
        $this->assertTrue(file_exists($dir));
        $this->assertTrue(file_exists("$dir/foo"));
        $this->assertTrue(file_exists("$dir/baz"));
        $this->assertTrue(file_exists("$dir/baz/.qux"));
        deltree($dir);
        $this->assertFalse(file_exists($dir));
    }
}
