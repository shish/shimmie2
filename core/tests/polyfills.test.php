<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once "core/polyfills.php";

class PolyfillsTest extends TestCase
{
    public function test_html_escape()
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

    public function test_int_escape()
    {
        $this->assertEquals(0, int_escape(""));
        $this->assertEquals(1, int_escape("1"));
        $this->assertEquals(-1, int_escape("-1"));
        $this->assertEquals(-1, int_escape("-1.5"));
        $this->assertEquals(0, int_escape(null));
    }

    public function test_url_escape()
    {
        $this->assertEquals("%5E%5Co%2F%5E", url_escape("^\o/^"));
        $this->assertEquals("", url_escape(null));
    }

    public function test_bool_escape()
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

    public function test_clamp()
    {
        $this->assertEquals(5, clamp(0, 5, 10));
        $this->assertEquals(5, clamp(5, 5, 10));
        $this->assertEquals(7, clamp(7, 5, 10));
        $this->assertEquals(10, clamp(10, 5, 10));
        $this->assertEquals(10, clamp(15, 5, 10));
    }

    public function test_truncate()
    {
        $this->assertEquals("test words", truncate("test words", 10));
        $this->assertEquals("test...", truncate("test...", 9));
        $this->assertEquals("test...", truncate("test...", 6));
        $this->assertEquals("te...", truncate("te...", 2));
    }

    public function test_to_shorthand_int()
    {
        $this->assertEquals("1.1GB", to_shorthand_int(1231231231));
        $this->assertEquals("2", to_shorthand_int(2));
    }

    public function test_parse_shorthand_int()
    {
        $this->assertEquals(-1, parse_shorthand_int("foo"));
        $this->assertEquals(33554432, parse_shorthand_int("32M"));
        $this->assertEquals(44441, parse_shorthand_int("43.4KB"));
        $this->assertEquals(1231231231, parse_shorthand_int("1231231231"));
    }

    public function test_format_milliseconds()
    {
        $this->assertEquals("", format_milliseconds(5));
        $this->assertEquals("5s", format_milliseconds(5000));
        $this->assertEquals("1y 213d 16h 53m 20s", format_milliseconds(50000000000));
    }

    public function test_parse_to_milliseconds()
    {
        $this->assertEquals(10, parse_to_milliseconds("10"));
        $this->assertEquals(5000, parse_to_milliseconds("5s"));
        $this->assertEquals(50000000000, parse_to_milliseconds("1y 213d 16h 53m 20s"));
    }

    public function test_autodate()
    {
        $this->assertEquals(
            "<time datetime='2012-06-23T16:14:22+00:00'>June 23, 2012; 16:14</time>",
            autodate("2012-06-23 16:14:22")
        );
    }

    public function test_validate_input()
    {
        $_POST = [
            "foo" => " bar ",
            "to_null" => "  ",
            "num" => "42",
        ];
        $this->assertEquals(
            ["foo"=>"bar"],
            validate_input(["foo"=>"string,trim,lower"])
        );
        //$this->assertEquals(
        //    ["to_null"=>null],
        //    validate_input(["to_null"=>"string,trim,nullify"])
        //);
        $this->assertEquals(
            ["num"=>42],
            validate_input(["num"=>"int"])
        );
    }

    public function test_sanitize_path()
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

    public function test_join_path()
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

    public function test_stringer()
    {
        $this->assertEquals(
            '["foo"=>"bar", "baz"=>[1, 2, 3], "qux"=>["a"=>"b"]]',
            stringer(["foo"=>"bar", "baz"=>[1,2,3], "qux"=>["a"=>"b"]])
        );
    }
}
