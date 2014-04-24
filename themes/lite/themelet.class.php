<?php
class Themelet extends BaseThemelet {
	/**
	 * Put something in a rounded rectangle box; specific to the default theme
	 */
	public function rr($html) {
		return "
			<div class='tframe'>
				$html
			</div>
		";
	}

	/**
	 * Add a generic paginator
	 */
	public function display_paginator(Page $page, $base, $query, $page_number, $total_pages) {
		if($total_pages == 0) $total_pages = 1;
		$body = $this->build_paginator($page_number, $total_pages, $base, $query);
		$page->add_block(new Block(null, $body, "main", 90));
	}

	private function gen_page_link($base_url, $query, $page, $name, $link_class=null) {
		$link = make_link("$base_url/$page", $query);
	    return "<a class='$link_class' href='$link'>$name</a>";
	}
	
	private function gen_page_link_block($base_url, $query, $page, $current_page, $name) {
		$paginator = "";
	    
	    if($page == $current_page) {$link_class = "tab-selected";} else {$link_class = "";}
	    $paginator .= $this->gen_page_link($base_url, $query, $page, $name, $link_class);
	    
	    return $paginator;
	}
					
	private function build_paginator($current_page, $total_pages, $base_url, $query) {
		$next = $current_page + 1;
		$prev = $current_page - 1;
		$rand = mt_rand(1, $total_pages);

		$at_start = ($current_page <= 1 || $total_pages <= 1);
		$at_end = ($current_page >= $total_pages);

		$first_html  = $at_start ? "<span class='tab'>First</span>" : $this->gen_page_link($base_url, $query, 1,            "First");
		$prev_html   = $at_start ? "<span class='tab'>Prev</span>"  : $this->gen_page_link($base_url, $query, $prev,        "Prev");
		$random_html =                       			      $this->gen_page_link($base_url, $query, $rand,        "Random");
		$next_html   = $at_end   ? "<span class='tab'>Next</span>"  : $this->gen_page_link($base_url, $query, $next,        "Next");
		$last_html   = $at_end   ? "<span class='tab'>Last</span>"  : $this->gen_page_link($base_url, $query, $total_pages, "Last");

		$start = $current_page-5 > 1 ? $current_page-5 : 1;
		$end = $start+10 < $total_pages ? $start+10 : $total_pages;

		$pages = array();
		foreach(range($start, $end) as $i) {
			$pages[] = $this->gen_page_link_block($base_url, $query, $i, $current_page, $i);
		}
		$pages_html = implode(" ", $pages);

		return "<div class='paginator sfoot'>
			$first_html
			$prev_html
			$random_html
			&lt;&lt; $pages_html &gt;&gt;
			$next_html $last_html
			</div>";
	}
}

