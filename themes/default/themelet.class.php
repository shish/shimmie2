<?php

class Themelet {
	public function display_error($page, $title, $message) {
		$page->set_title($title);
		$page->set_heading($title);
		$page->add_side_block(new NavBlock());
		$page->add_main_block(new Block("Error", $message));
	}
}
?>
