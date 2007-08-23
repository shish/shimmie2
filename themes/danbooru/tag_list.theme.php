<?php

class CustomTagListTheme extends TagListTheme {
	var $heading = "";
	var $list = "";
	
	public function display_page($page) {
		$page->disable_left();
		$page->set_title("Tag List");
		$page->set_heading($this->heading);
		$page->add_block(new Block("Navigation", str_replace("<br>", ", ", $this->navigation), "main", 0));
		$page->add_block(new Block("&nbsp;", $this->list));
	}
}
?>
