<?php

class RandomListTheme extends Themelet {
	protected $search_terms;

	/**
	 * @param string[] $search_terms
	 */
	public function set_page($search_terms) {
		$this->search_terms = $search_terms;
	}

	/**
	 * @param Page $page
	 * @param Image[] $images
	 */
	public function display_page(Page $page, $images) {
		$page->title = "Random Images";

		$html = "<b>Refresh the page to view more images</b>
		<div class='shm-image-list'>";

		foreach ($images as $image)
			$html .= $this->build_thumb_html($image);

		$html .= "</div>";
		$page->add_block(new Block("Random Images", $html));

		$nav = $this->build_navigation($this->search_terms);
		$page->add_block(new Block("Navigation", $nav, "left", 0));
	}

	/**
	 * @param string[] $search_terms
	 * @return string
	 */
	protected function build_navigation($search_terms) {
		$h_search_string = html_escape(implode(" ", $search_terms));
		$h_search_link = make_link("random");
		$h_search = "
			<p><form action='$h_search_link' method='GET'>
				<input type='search' name='search' value='$h_search_string' placeholder='Search random list' class='autocomplete_tags' autocomplete='off' />
				<input type='hidden' name='q' value='/random'>
				<input type='submit' value='Find' style='display: none;' />
			</form>
		";

		return $h_search;
	}
}

