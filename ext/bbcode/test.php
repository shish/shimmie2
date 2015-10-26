<?php
class BBCodeTest extends ShimmiePHPUnitTestCase {
	public function testBasics() {
		$this->assertEquals(
			$this->filter("[b]bold[/b][i]italic[/i]"),
			"<b>bold</b><i>italic</i>");
	}

	public function testStacking() {
		$this->assertEquals(
			$this->filter("[b]B[/b][i]I[/i][b]B[/b]"),
			"<b>B</b><i>I</i><b>B</b>");
		$this->assertEquals(
			$this->filter("[b]bold[i]bolditalic[/i]bold[/b]"),
			"<b>bold<i>bolditalic</i>bold</b>");
	}

	public function testFailure() {
		$this->assertEquals(
			$this->filter("[b]bold[i]italic"),
			"[b]bold[i]italic");
	}

	public function testCode() {
		$this->assertEquals(
			$this->filter("[code][b]bold[/b][/code]"),
			"<pre>[b]bold[/b]</pre>");
	}

	public function testNestedList() {
		$this->assertEquals(
			$this->filter("[list][*]a[list][*]a[*]b[/list][*]b[/list]"),
			"<ul><li>a<ul><li>a<li>b</ul><li>b</ul>");
		$this->assertEquals(
			$this->filter("[ul][*]a[ol][*]a[*]b[/ol][*]b[/ul]"),
			"<ul><li>a<ol><li>a<li>b</ol><li>b</ul>");
	}

	public function testSpoiler() {
		$this->assertEquals(
			$this->filter("[spoiler]ShishNet[/spoiler]"),
			"<span style=\"background-color:#000; color:#000;\">ShishNet</span>");
		$this->assertEquals(
			$this->strip("[spoiler]ShishNet[/spoiler]"),
			"FuvfuArg");
		#$this->assertEquals(
		#	$this->filter("[spoiler]ShishNet"),
		#	"[spoiler]ShishNet");
	}

	public function testURL() {
		$this->assertEquals(
			$this->filter("[url]http://shishnet.org[/url]"),
			"<a href=\"http://shishnet.org\">http://shishnet.org</a>");
		$this->assertEquals(
			$this->filter("[url=http://shishnet.org]ShishNet[/url]"),
			"<a href=\"http://shishnet.org\">ShishNet</a>");
		$this->assertEquals(
			$this->filter("[url=javascript:alert(\"owned\")]click to fail[/url]"),
			"[url=javascript:alert(\"owned\")]click to fail[/url]");
	}

	public function testEmailURL() {
		$this->assertEquals(
			$this->filter("[email]spam@shishnet.org[/email]"),
			"<a href=\"mailto:spam@shishnet.org\">spam@shishnet.org</a>");
	}

	public function testAnchor() {
		$this->assertEquals(
			$this->filter("[anchor=rules]Rules[/anchor]"),
			'<span class="anchor">Rules <a class="alink" href="#bb-rules" name="bb-rules" title="link to this anchor"> ¶ </a></span>');
	}

	private function filter($in) {
		$bb = new BBCode();
		return $bb->format($in);
	}

	private function strip($in) {
		$bb = new BBCode();
		return $bb->strip($in);
	}
}

