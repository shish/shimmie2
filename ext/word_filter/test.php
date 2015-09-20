<?php
class WordFilterTest extends ShimmiePHPUnitTestCase {
	function setUp() {
		global $config;
		parent::setUp();
		$config->set_string("word_filter", "whore,nice lady\na duck,a kitten\n white ,\tspace\ninvalid");
	}

	function _doThings($in, $out) {
		global $user;
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
		send_event(new CommentPostingEvent($image_id, $user, $in));
		$this->get_page("post/view/$image_id");
		$this->assert_text($out);
	}

	function testRegular() {
		$this->_doThings(
			"posted by a whore",
			"posted by a nice lady"
		);
	}

	function testReplaceAll() {
		$this->_doThings(
			"a whore is a whore is a whore",
			"a nice lady is a nice lady is a nice lady"
		);
	}

	function testMixedCase() {
		$this->_doThings(
			"monkey WhorE",
			"monkey nice lady"
		);
	}

	function testOnlyWholeWords() {
		$this->_doThings(
			"my name is whoretta",
			"my name is whoretta"
		);
	}

	function testMultipleWords() {
		$this->_doThings(
			"I would like a duck",
			"I would like a kitten"
		);
	}

	function testWhitespace() {
		$this->_doThings(
			"A colour is white",
			"A colour is space"
		);
	}

	function testIgnoreInvalid() {
		$this->_doThings(
			"The word was invalid",
			"The word was invalid"
		);
	}
}

