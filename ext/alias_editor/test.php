<?php
class AliasEditorTest extends ShimmieWebTestCase {
	function testAliasEditor() {
        $this->get_page('alias/list');
		$this->assertTitle("Alias List");

		$this->log_in_as_admin();

		# test one to one
        $this->get_page('alias/list');
		$this->assertTitle("Alias List");
		$this->setField('oldtag', "test1");
		$this->setField('newtag', "test2");
		$this->click("Add");
		$this->assertText("test1");

		$this->get_page("alias/export/aliases.csv");
		$this->assertText("test1,test2");

		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "test1");
		$this->get_page("post/view/$image_id"); # check that the tag has been replaced
		$this->assertTitle("Image $image_id: test2");
		$this->get_page("post/list/test1/1"); # searching for an alias should find the master tag
		$this->assertTitle("Image $image_id: test2");
		$this->get_page("post/list/test2/1"); # check that searching for the main tag still works
		$this->assertTitle("Image $image_id: test2");
		$this->delete_image($image_id);

        $this->get_page('alias/list');
		$this->click("Remove");
		$this->assertTitle("Alias List");
		$this->assertNoText("test1");

		# test one to many
        $this->get_page('alias/list');
		$this->assertTitle("Alias List");
		$this->setField('oldtag', "onetag");
		$this->setField('newtag', "multi tag");
		$this->click("Add");
		$this->assertText("multi");
		$this->assertText("tag");

		$this->get_page("alias/export/aliases.csv");
		$this->assertText("onetag,multi tag");

		$image_id_1 = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "onetag");
		$image_id_2 = $this->post_image("ext/simpletest/data/bedroom_workshop.jpg", "onetag");
		// FIXME: known broken
		//$this->get_page("post/list/onetag/1"); # searching for an aliased tag should find its aliases
		//$this->assertTitle("onetag");
		//$this->assertNoText("No Images Found");
		$this->get_page("post/list/multi/1");
		$this->assertTitle("multi");
		$this->assertNoText("No Images Found");
		$this->get_page("post/list/multi%20tag/1");
		$this->assertTitle("multi tag");
		$this->assertNoText("No Images Found");
		$this->delete_image($image_id_1);
		$this->delete_image($image_id_2);

        $this->get_page('alias/list');
		$this->click("Remove");
		$this->assertTitle("Alias List");
		$this->assertNoText("test1");

		$this->log_out();
	}
}
?>
