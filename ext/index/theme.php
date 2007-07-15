<?php

class IndexTheme extends Themelet {
	public function set_page($page_number, $total_pages, $search_terms) {
		$this->page_number = $page_number;
		$this->total_pages = $total_pages;
		$this->search_terms = $search_terms;
	}

	public function display_page($page, $images) {
		global $config;

		if(count($this->search_terms) == 0) {
			$query = null;
			$page_title = $config->get_string('title');
		}
		else {
			$search_string = implode(' ', $this->search_terms);
			$query = "search=".url_escape($search_string);
			$page_title = html_escape($search_string);
			if(count($images) > 0) {
				$page->set_subheading("Page {$this->page_number} / {$this->total_pages}");
			}
		}
		if($this->page_number > 1 || count($this->search_terms) > 0) {
			// $page_title .= " / $page_number";
		}

		$nav = $this->build_navigation($this->page_number, $this->total_pages, $this->search_terms);
		$page->set_title($page_title);
		$page->set_heading($page_title);
		$page->add_block(new Block("Navigation", $nav, "left", 0));
		if(count($images) > 0) {
			$page->add_block(new Block("Images", $this->build_table($images, $query), "main", 10));
			$page->add_block(new Paginator("post/list", $query, $this->page_number, $this->total_pages));
		}
		else {
			$page->add_block(new Block("No Images Found", "No images were found to match the search criteria"));
		}
	}


	private function build_navigation($page_number, $total_pages, $search_terms) {
		$prev = $page_number - 1;
		$next = $page_number + 1;

		$u_tags = url_escape(implode(" ", $search_terms));
		$query = empty($u_tags) ? null : "/$u_tags";

		
		$h_prev = ($page_number <= 1) ? "Prev" : "<a href='".make_link("post/list/$prev", $query)."'>Prev</a>";
		$h_index = "<a href='".make_link("index")."'>Index</a>";
		$h_next = ($page_number >= $total_pages) ? "Next" : "<a href='".make_link("post/list/$next", $query)."'>Next</a>";

		$h_search_string = count($search_terms) == 0 ? "Search" : html_escape(implode(" ", $search_terms));
		$h_search_link = make_link("index");
		$h_search = "
			<p><form action='$h_search_link' method='GET'>
				<input id='search_input' name='search' type='text'
						value='$h_search_string' autocomplete='off' />
				<input type='submit' value='Find' style='display: none;' />
			</form>
			<div id='search_completions'></div>";

		return "$h_prev | $h_index | $h_next<br>$h_search";
	}

	private function build_table($images, $query) {
		global $config;

		$width = $config->get_int('index_width');
		$height = $config->get_int('index_height');

		$table = "<table>\n";
		for($i=0; $i<$height; $i++) {
			$table .= "<tr>\n";
			for($j=0; $j<$width; $j++) {
				$image = isset($images[$i*$width+$j]) ? $images[$i*$width+$j] : null;
				if(!is_null($image)) {
					$table .= "\t<td>" . build_thumb_html($image, $query) . "</td>\n";
				}
				else {
					$table .= "\t<td>&nbsp;</td>\n";
				}
			}
			$table .= "</tr>\n";
		}
		$table .= "</table>\n";

		return $table;
	}
}
?>
