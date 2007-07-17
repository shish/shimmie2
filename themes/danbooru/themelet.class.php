<?php

class Themelet {
	public function display_error($page, $title, $message) {
		$page->set_title($title);
		$page->set_heading($title);
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Error", $message));
	}
}
?>
