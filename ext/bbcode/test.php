<?php
class BBCodeUnitTest extends UnitTestCase {
	public function testBasics() {
		$this->template("[b]bold[/b][i]italic[/i]", "<b>bold</b><i>italic</i>");
	}

	public function testStacking() {
		$this->template("[b]B[/b][i]I[/i][b]B[/b]", "<b>B</b><i>I</i><b>B</b>");
		$this->template("[b]bold[i]bolditalic[/i]bold[/b]", "<b>bold<i>bolditalic</i>bold</b>");
	}

	public function testFailure() {
		$this->template("[b]bold[i]italic", "[b]bold[i]italic");
	}

	public function testURL() {
		$this->template(
			"[url]http://shishnet.org[/url]",
			"<a href=\"http://shishnet.org\">http://shishnet.org</a>");
		$this->template(
			"[url=http://shishnet.org]ShishNet[/url]",
			"<a href=\"http://shishnet.org\">ShishNet</a>");
		$this->template(
			"[url=javascript:alert(\"owned\")]click to fail[/url]",
			"[url=javascript:alert(&quot;owned&quot;)]click to fail[/url]");
	}

	private function template($in, $out) {
		$bb = new BBCode();
		$tfe = new TextFormattingEvent($in);
		$bb->receive_event($tfe);
		$this->assertEqual($tfe->formatted, $out);
	}
}
?>
