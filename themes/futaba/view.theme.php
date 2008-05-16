<?php

class CustomViewTheme extends ViewTheme {
	public function display_page($page, $image, $editor_parts) {
		$page->set_title("Image {$image->id}: ".html_escape($image->get_tag_list()));
		$page->set_heading(html_escape($image->get_tag_list()));
		$page->add_block(new Block("Navigation", $this->build_navigation($image->id), "left", 0));
		$page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 10));
	}
	
	protected function build_navigation($image_id) {
		$h_search = "
			<p><form action='".make_link()."' method='GET'>
				<input id='search_input' name='search' type='text'
						value='Search' autocomplete='off'>
				<input type='submit' value='Find' style='display: none;'>
			</form>
			<div id='search_completions'></div>";
		return $h_search;
	}
}
?>
