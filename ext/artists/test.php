<?php
class ArtistTest extends ShimmieWebTestCase {
	function testSearch() {
		# FIXME: check that the results are there
		$this->get_page("post/list/author=bob/1");
	}
}

