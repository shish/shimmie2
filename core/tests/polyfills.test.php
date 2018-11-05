<?php
require_once "core/polyfills.php";

class PolyfillsTest extends \PHPUnit\Framework\TestCase {
	public function test_html_escape() {
		$this->assertEquals(
			html_escape("Foo & <waffles>"),
			"Foo &amp; &lt;waffles&gt;"
		);

		$this->assertEquals(
			html_unescape("Foo &amp; &lt;waffles&gt;"),
			"Foo & <waffles>"
		);

		$x = "Foo &amp; &lt;waffles&gt;";
		$this->assertEquals(html_escape(html_unescape($x)), $x);
	}

	public function test_int_escape() {
		$this->assertEquals(int_escape(""), 0);
		$this->assertEquals(int_escape("1"), 1);
		$this->assertEquals(int_escape("-1"), -1);
		$this->assertEquals(int_escape("-1.5"), -1);
	}

	public function test_clamp() {
		$this->assertEquals(clamp(0, 5, 10), 5);
		$this->assertEquals(clamp(5, 5, 10), 5);
		$this->assertEquals(clamp(7, 5, 10), 7);
		$this->assertEquals(clamp(10, 5, 10), 10);
		$this->assertEquals(clamp(15, 5, 10), 10);
	}

	public function test_shorthand_int() {
		$this->assertEquals(to_shorthand_int(1231231231), "1.1GB");

		$this->assertEquals(parse_shorthand_int("foo"), -1);
		$this->assertEquals(parse_shorthand_int("32M"), 33554432);
		$this->assertEquals(parse_shorthand_int("43.4KB"), 44441);
		$this->assertEquals(parse_shorthand_int("1231231231"), 1231231231);
	}
}
