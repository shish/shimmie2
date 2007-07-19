<?php

class Tag_HistoryTheme extends Themelet {
	public function display_history_page($page, $image_id, $history) {
		$page_heading = "Tag History: $image_id";
		$page->set_title("Image $image_id Tag History");
		$page->set_heading($page_heading);
						
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Tag History", $history, "main", 10));
	}

	public function display_history_link($page, $image_id) {
		$link = "<a href='".make_link("tag_history/$image_id")."'>Tag History</a>\n";
		$page->add_block(new Block(null, $link, "main", 5));
	}
}
?>
