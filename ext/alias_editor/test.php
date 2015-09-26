<?php
class AliasEditorTest extends ShimmiePHPUnitTestCase {
	public function testAliasList() {
		$this->get_page('alias/list');
		$this->assert_response(200);
		$this->assert_title("Alias List");
	}

	public function testAliasListReadOnly() {
		// Check that normal users can't add aliases.
		$this->log_in_as_user();
		$this->get_page('alias/list');
		$this->assert_title("Alias List");
		$this->assert_no_text("Add");
	}

	public function testAliasEditor() {
		/*
		 **********************************************************************
		 * FIXME: TODO:
		 *  For some reason the alias tests always fail when they are running
		 *  inside the TravisCI VM environment. I have tried to determine
		 *  the exact cause of this, but have been unable to pin it down.
		 *
		 *  For now, I am commenting them out until I have more time to
		 *  dig into this and determine exactly what is happening.
		 *
		 *********************************************************************
		*/
		$this->markTestIncomplete();

		$this->log_in_as_admin();

		# test one to one
		$this->get_page('alias/list');
		$this->assert_title("Alias List");
		$this->set_field('oldtag', "test1");
		$this->set_field('newtag', "test2");
		$this->clickSubmit('Add');
		$this->assert_no_text("Error adding alias");

		$this->get_page('alias/list');
		$this->assert_text("test1");

		$this->get_page("alias/export/aliases.csv");
		$this->assert_text("test1,test2");

		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "test1");
		$this->get_page("post/view/$image_id"); # check that the tag has been replaced
		$this->assert_title("Image $image_id: test2");
		$this->get_page("post/list/test1/1"); # searching for an alias should find the master tag
		$this->assert_title("Image $image_id: test2");
		$this->get_page("post/list/test2/1"); # check that searching for the main tag still works
		$this->assert_title("Image $image_id: test2");
		$this->delete_image($image_id);

		$this->get_page('alias/list');
		$this->click("Remove");
		$this->get_page('alias/list');
		$this->assert_title("Alias List");
		$this->assert_no_text("test1");

		# test one to many
		$this->get_page('alias/list');
		$this->assert_title("Alias List");
		$this->set_field('oldtag', "onetag");
		$this->set_field('newtag', "multi tag");
		$this->click("Add");
		$this->get_page('alias/list');
		$this->assert_text("multi");
		$this->assert_text("tag");

		$this->get_page("alias/export/aliases.csv");
		$this->assert_text("onetag,multi tag");

		$image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "onetag");
		$image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "onetag");
		// FIXME: known broken
		//$this->get_page("post/list/onetag/1"); # searching for an aliased tag should find its aliases
		//$this->assert_title("onetag");
		//$this->assert_no_text("No Images Found");
		$this->get_page("post/list/multi/1");
		$this->assert_title("multi");
		$this->assert_no_text("No Images Found");
		$this->get_page("post/list/multi%20tag/1");
		$this->assert_title("multi tag");
		$this->assert_no_text("No Images Found");
		$this->delete_image($image_id_1);
		$this->delete_image($image_id_2);

		$this->get_page('alias/list');
		$this->click("Remove");
		$this->get_page('alias/list');
		$this->assert_title("Alias List");
		$this->assert_no_text("test1");

		$this->log_out();

		$this->get_page('alias/list');
		$this->assert_title("Alias List");
		$this->assert_no_text("Add");
	}
}

