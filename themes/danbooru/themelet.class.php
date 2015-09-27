<?php
class Themelet extends BaseThemelet {
	/**
	 * @param Page $page
	 * @param string $base
	 * @param string $query
	 * @param int $page_number
	 * @param int $total_pages
	 * @param bool $show_random
	 */
	public function display_paginator(Page $page, $base, $query, $page_number, $total_pages, $show_random = FALSE) {
		if($total_pages == 0) $total_pages = 1;
		$body = $this->build_paginator($page_number, $total_pages, $base, $query);
		$page->add_block(new Block(null, $body, "main", 90));
	}

	/**
	 * @param string $base_url
	 * @param string $query
	 * @param int|string $page
	 * @param string $name
	 * @return string
	 */
	private function gen_page_link($base_url, $query, $page, $name) {
		$link = make_link("$base_url/$page", $query);
	    return "<a href='$link'>$name</a>";
	}

	/**
	 * @param string $base_url
	 * @param string $query
	 * @param int|string $page
	 * @param int $current_page
	 * @param string $name
	 * @return string
	 */
	private function gen_page_link_block($base_url, $query, $page, $current_page, $name) {
		$paginator = "";
	    if($page == $current_page) $paginator .= "<b>$page</b>";
	    else $paginator .= $this->gen_page_link($base_url, $query, $page, $name);
	    return $paginator;
	}

	/**
	 * @param int $current_page
	 * @param int $total_pages
	 * @param string $base_url
	 * @param string $query
	 * @return string
	 */
	private function build_paginator($current_page, $total_pages, $base_url, $query) {
		$next = $current_page + 1;
		$prev = $current_page - 1;

		$at_start = ($current_page <= 3 || $total_pages <= 3);
		$at_end = ($current_page >= $total_pages -2);

		$first_html  = $at_start ? "" : $this->gen_page_link($base_url, $query, 1,            "1");
		$prev_html   = $at_start ? "" : $this->gen_page_link($base_url, $query, $prev,        "&lt;&lt;");
		$next_html   = $at_end   ? "" : $this->gen_page_link($base_url, $query, $next,        "&gt;&gt;");
		$last_html   = $at_end   ? "" : $this->gen_page_link($base_url, $query, $total_pages, "$total_pages");

		$start = $current_page-2 > 1 ? $current_page-2 : 1;
		$end   = $current_page+2 <= $total_pages ? $current_page+2 : $total_pages;

		$pages = array();
		foreach(range($start, $end) as $i) {
			$pages[] = $this->gen_page_link_block($base_url, $query, $i, $current_page, $i);
		}
		$pages_html = implode(" ", $pages);

		if(strlen($first_html) > 0) $pdots = "...";
		else $pdots = "";

		if(strlen($last_html) > 0) $ndots = "...";
		else $ndots = "";

		return "<div id='paginator'>$prev_html $first_html $pdots $pages_html $ndots $last_html $next_html</div>";
	}
}

