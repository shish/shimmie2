<?php
/**
 * A collection of common functions for theme parts
 */
class Themelet {
	/**
	 * Generic error message display
	 */
	public function display_error(Page $page, $title, $message) {
		$page->set_title($title);
		$page->set_heading($title);
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Error", $message));
	}


	/**
	 * A specific, common error message
	 */
	public function display_permission_denied(Page $page) {
		header("HTTP/1.0 403 Permission Denied");
		$this->display_error($page, "Permission Denied", "You do not have permission to access this page");
	}


	/**
	 * Generic thumbnail code; returns HTML rather than adding
	 * a block since thumbs tend to go inside blocks...
	 */
	public function build_thumb_html(Image $image, $query=null) {
		global $config;
		$i_id = int_escape($image->id);
		$h_view_link = make_link("post/view/$i_id", $query);
		$h_tip = html_escape($image->get_tooltip());
		$h_thumb_link = $image->get_thumb_link();
		$tsize = get_thumbnail_size($image->width, $image->height);
		return "
			<div class='thumbblock'>
			
				<a href='$h_view_link' style='position: relative; display: block; height: {$tsize[1]}px; width: {$tsize[0]}px;'>
					<img id='thumb_$i_id' title='$h_tip' alt='$h_tip' class='highlighted' style='height: {$tsize[1]}px; width: {$tsize[0]}px;' src='$h_thumb_link'>
				</a>
			
			</div>
		";
	}


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
		$rand = rand(1, $total_pages);

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
?>
