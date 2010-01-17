<?php
# FIXME: web test
class BBCodeTest extends ShimmieWebTestCase {}

if(!defined(VERSION)) return;

class BBCodeUnitTest extends UnitTestCase {
	public function testBasics() {
		$this->assertEqual(
			$this->filter("[b]bold[/b][i]italic[/i]"),
			"<b>bold</b><i>italic</i>");
	}

	public function testStacking() {
		$this->assertEqual(
			$this->filter("[b]B[/b][i]I[/i][b]B[/b]"),
			"<b>B</b><i>I</i><b>B</b>");
		$this->assertEqual(
			$this->filter("[b]bold[i]bolditalic[/i]bold[/b]"),
			"<b>bold<i>bolditalic</i>bold</b>");
	}

	public function testFailure() {
		$this->assertEqual(
			$this->filter("[b]bold[i]italic"),
			"[b]bold[i]italic");
	}

	public function testCode() {
		$this->assertEqual(
			$this->filter("[code][b]bold[/b][/code]"),
			"<pre>[b]bold[/b]</pre>");
	}

	public function testNestedList() {
		$this->assertEqual(
			$this->filter("[list][*]a[list][*]a[*]b[/list][*]b[/list]"),
			"<ul><li>a<ul><li>a<li>b</ul><li>b</ul>");
	}

	public function testSpoiler() {
		$this->assertEqual(
			$this->filter("[spoiler]ShishNet[/spoiler]"),
			"<span style=\"background-color:#000; color:#000;\">ShishNet</span>");
		$this->assertEqual(
			$this->strip("[spoiler]ShishNet[/spoiler]"),
			"FuvfuArg");
		#$this->assertEqual(
		#	$this->filter("[spoiler]ShishNet"),
		#	"[spoiler]ShishNet");
	}

	public function testURL() {
		$this->assertEqual(
			$this->filter("[url]http://shishnet.org[/url]"),
			"<a href=\"http://shishnet.org\">http://shishnet.org</a>");
		$this->assertEqual(
			$this->filter("[url=http://shishnet.org]ShishNet[/url]"),
			"<a href=\"http://shishnet.org\">ShishNet</a>");
		$this->assertEqual(
			$this->filter("[url=javascript:alert(\"owned\")]click to fail[/url]"),
			"[url=javascript:alert(&quot;owned&quot;)]click to fail[/url]");
	}

	public function testEmailURL() {
		$this->assertEqual(
			$this->filter("[email]spam@shishnet.org[/email]"),
			"<a href=\"mailto:spam@shishnet.org\">spam@shishnet.org</a>");
	}

	private function filter($in) {
		$bb = new BBCode();
		$tfe = new TextFormattingEvent($in);
		$bb->receive_event($tfe);
		return $tfe->formatted;
	}

	private function strip($in) {
		$bb = new BBCode();
		$tfe = new TextFormattingEvent($in);
		$bb->receive_event($tfe);
		return $tfe->stripped;
	}
}
?>
