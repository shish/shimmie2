<?php
class WordFilterTest extends ShimmieWebTestCase {
	function testWordFilter() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->setField("_config_word_filter", "whore,nice lady\na duck,a kitten\n white ,\tspace\ninvalid");
		$this->click("Save Settings");
		$this->log_out();

		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->get_page("post/view/$image_id");

		# regular
		$this->setField('comment', "posted by a whore");
		$this->click("Post Comment");
		$this->assertText("posted by a nice lady");

		# replace all instances
		$this->setField('comment', "a whore is a whore is a whore");
		$this->click("Post Comment");
		$this->assertText("a nice lady is a nice lady is a nice lady");

		# still have effect when case is changed
		$this->setField('comment', "monkey WhorE");
		$this->click("Post Comment");
		$this->assertText("monkey nice lady");

		# only do whole words
		$this->setField('comment', "my name is whoretta");
		$this->click("Post Comment");
		$this->assertText("my name is whoretta");

		# search multiple words
		$this->setField('comment', "I would like a duck");
		$this->click("Post Comment");
		$this->assertText("I would like a kitten");

		# test for a line which was entered with extra whitespace
		$this->setField('comment', "A colour is white");
		$this->click("Post Comment");
		$this->assertText("A colour is space");

		# don't do anything with invalid lines
		$this->setField('comment', "The word was invalid");
		$this->click("Post Comment");
		$this->assertText("The word was invalid");

		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
