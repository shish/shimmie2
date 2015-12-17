<?php

class IndexTheme extends Themelet {
	var $page_number, $total_pages, $search_terms;

	/**
	 * @param int $page_number
	 * @param int $total_pages
	 * @param string[] $search_terms
	 */
	public function set_page($page_number, $total_pages, $search_terms) {
		$this->page_number = $page_number;
		$this->total_pages = $total_pages;
		$this->search_terms = $search_terms;
	}

	public function display_intro(Page $page) {
		$text = "
<div style='text-align: left;'>
<p>The first thing you'll probably want to do is create a new account; note
that the first account you create will by default be marked as the board's
administrator, and any further accounts will be regular users.

<p>Once logged in you can play with the settings, install extra features,
and of course start organising your images :-)

<p>This message will go away once your first image is uploaded~
</div>
";
		$page->set_title("Welcome to Shimmie ".VERSION);
		$page->set_heading("Welcome to Shimmie");
		$page->add_block(new Block("Installation Succeeded!", $text, "main", 0));
	}

	/**
	 * @param Page $page
	 * @param Image[] $images
	 */
	public function display_page(Page $page, $images) {
		$this->display_page_header($page, $images);

		$nav = $this->build_navigation($this->page_number, $this->total_pages, $this->search_terms);
		$page->add_block(new Block("Navigation", $nav, "left", 0));

		if(count($images) > 0) {
			$this->display_page_images($page, $images);
		}
		else {
			$this->display_error(404, "No Images Found", "No images were found to match the search criteria");
		}
	}

	/**
	 * @param string[] $parts
	 */
	public function display_admin_block($parts) {
		global $page;
		$page->add_block(new Block("List Controls", join("<br>", $parts), "left", 50));
	}


	/**
	 * @param int $page_number
	 * @param int $total_pages
	 * @param string[] $search_terms
	 * @return string
	 */
	protected function build_navigation($page_number, $total_pages, $search_terms) {
		$prev = $page_number - 1;
		$next = $page_number + 1;

		$u_tags = url_escape(implode(" ", $search_terms));
		$query = empty($u_tags) ? "" : '/'.$u_tags;


		$h_prev = ($page_number <= 1) ? "Prev" : '<a href="'.make_link('post/list'.$query.'/'.$prev).'">Prev</a>';
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_next = ($page_number >= $total_pages) ? "Next" : '<a href="'.make_link('post/list'.$query.'/'.$next).'">Next</a>';

		$h_search_string = html_escape(implode(" ", $search_terms));
		$h_search_link = make_link();
		$h_search = "
			<p><form action='$h_search_link' method='GET'>
				<input type='search' name='search' value='$h_search_string' placeholder='Search' class='autocomplete_tags' autocomplete='off' />
				<input type='hidden' name='q' value='/post/list'>
				<input type='submit' value='Find' style='display: none;' />
			</form>
		";

		return $h_prev.' | '.$h_index.' | '.$h_next.'<br>'.$h_search;
	}

	/**
	 * @param Image[] $images
	 * @param string $query
	 * @return string
	 */
	protected function build_table($images, $query) {
		$h_query = html_escape($query);
		$table = "<div class='shm-image-list' data-query='$h_query'>";
		foreach($images as $image) {
			$table .= $this->build_thumb_html($image);
		}
		$table .= "</div>";
		return $table;
	}

	/**
	 * @param Page $page
	 * @param Image[] $images
	 */
	protected function display_page_header(Page $page, $images) {
		global $config;

		if (count($this->search_terms) == 0) {
			$page_title = $config->get_string('title');
		} else {
			$search_string = implode(' ', $this->search_terms);
			$page_title = html_escape($search_string);
			if (count($images) > 0) {
				$page->set_subheading("Page {$this->page_number} / {$this->total_pages}");
			}
		}
		if ($this->page_number > 1 || count($this->search_terms) > 0) {
			// $page_title .= " / $page_number";
		}

		$page->set_title($page_title);
		$page->set_heading($page_title);
	}

	/**
	 * @param Page $page
	 * @param Image[] $images
	 */
	protected function display_page_images(Page $page, $images) {
		if (count($this->search_terms) > 0) {
			$query = url_escape(implode(' ', $this->search_terms));
			$page->add_block(new Block("Images", $this->build_table($images, "#search=$query"), "main", 10, "image-list"));
			$this->display_paginator($page, "post/list/$query", null, $this->page_number, $this->total_pages, TRUE);
		} else {
			$page->add_block(new Block("Images", $this->build_table($images, null), "main", 10, "image-list"));
			$this->display_paginator($page, "post/list", null, $this->page_number, $this->total_pages, TRUE);
		}
	}
}

