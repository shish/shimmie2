<?php
class Themelet extends BaseThemelet {

	/**
	 * Add a generic paginator.
	 *
	 * @param Page $page
	 * @param string $base
	 * @param string $query
	 * @param int $page_number
	 * @param int $total_pages
	 * @param bool $show_random
	 */
	public function display_paginator(Page $page, $base, $query, $page_number, $total_pages, $show_random = FALSE) {
		if($total_pages == 0) $total_pages = 1;
		$body = $this->futaba_build_paginator($page_number, $total_pages, $base, $query);
		$page->add_block(new Block(null, $body, "main", 90));
	}

	/**
	 * Generate a single HTML link.
	 *
	 * @param string $base_url
	 * @param string $query
	 * @param int|string $page
	 * @param string $name
	 * @return string
	 */
	public function futaba_gen_page_link($base_url, $query, $page, $name) {
		$link = make_link("$base_url/$page", $query);
	    return "[<a href='$link'>{$name}</a>]";
	}

	/**
	 * @param string $base_url
	 * @param string $query
	 * @param int|string $page
	 * @param int|string $current_page
	 * @param string $name
	 * @return string
	 */
	public function futaba_gen_page_link_block($base_url, $query, $page, $current_page, $name) {
		$paginator = "";
	    if($page == $current_page) $paginator .= "<b>";
	    $paginator .= $this->futaba_gen_page_link($base_url, $query, $page, $name);
	    if($page == $current_page) $paginator .= "</b>";
	    return $paginator;
	}

	/**
	 * Build the paginator.
	 *
	 * @param int $current_page
	 * @param int $total_pages
	 * @param string $base_url
	 * @param string $query
	 * @return string
	 */
	public function futaba_build_paginator($current_page, $total_pages, $base_url, $query) {
		$next = $current_page + 1;
		$prev = $current_page - 1;
		//$rand = mt_rand(1, $total_pages);

		$at_start = ($current_page <= 1 || $total_pages <= 1);
		$at_end = ($current_page >= $total_pages);

		//$first_html   = $at_start ? "First" : $this->futaba_gen_page_link($base_url, $query, 1,            "First");
		$prev_html      = $at_start ? "Prev"  : $this->futaba_gen_page_link($base_url, $query, $prev,        "Prev");
		//$random_html  =                       $this->futaba_gen_page_link($base_url, $query, $rand,        "Random");
		$next_html      = $at_end   ? "Next"  : $this->futaba_gen_page_link($base_url, $query, $next,        "Next");
		//$last_html    = $at_end   ? "Last"  : $this->futaba_gen_page_link($base_url, $query, $total_pages, "Last");

		$start = $current_page-5 > 1 ? $current_page-5 : 1;
		$end = $start+10 < $total_pages ? $start+10 : $total_pages;

		$pages = array();
		foreach(range($start, $end) as $i) {
			$pages[] = $this->futaba_gen_page_link_block($base_url, $query, $i, $current_page, $i);
		}
		$pages_html = implode(" ", $pages);

		//return "<p class='paginator'>$first_html | $prev_html | $random_html | $next_html | $last_html".
		//		"<br>&lt;&lt; $pages_html &gt;&gt;</p>";
		return "<p class='paginator'>{$prev_html} {$pages_html} {$next_html}</p>";
	}
}

