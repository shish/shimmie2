<?php
class WordFiterTest extends ShimmieWebTestCase {}

if(!defined(VERSION)) return;

class WordFilterUnitTest extends UnitTestCase {
	public function testURL() {
		$this->assertEqual(
			$this->filter("whore"),
			"nice lady");
	}

	private function filter($in) {
		$bb = new WordFilter();
		$tfe = new TextFormattingEvent($in);
		$bb->receive_event($tfe);
		return $tfe->formatted;
	}
}
?>
