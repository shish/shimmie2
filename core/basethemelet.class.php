<?php

/**
 * Class BaseThemelet
 *
 * A collection of common functions for theme parts
 */
class BaseThemelet {

	/**
	 * Generic error message display
	 *
	 * @param int $code
	 * @param string $title
	 * @param string $message
	 */
	public function display_error(/*int*/ $code, /*string*/ $title, /*string*/ $message) {
		global $page;
		$page->set_code($code);
		$page->set_title($title);
		$page->set_heading($title);
		$has_nav = false;
		foreach($page->blocks as $block) {
			if($block->header == "Navigation") {
				$has_nav = true;
				break;
			}
		}
		if(!$has_nav) {
			$page->add_block(new NavBlock());
		}
		$page->add_block(new Block("Error", $message));
	}

	/**
	 * A specific, common error message
	 */
	public function display_permission_denied() {
		$this->display_error(403, "Permission Denied", "You do not have permission to access this page");
	}


	/**
	 * Generic thumbnail code; returns HTML rather than adding
	 * a block since thumbs tend to go inside blocks...
	 *
	 * @param Image $image
	 * @return string
	 */
	public function build_thumb_html(Image $image) {
		global $config;

		$i_id = (int) $image->id;
		$h_view_link = make_link('post/view/'.$i_id);
		$h_thumb_link = $image->get_thumb_link();
		$h_tip = html_escape($image->get_tooltip());
		$h_tags = strtolower($image->get_tag_list());

		$extArr = array_flip(array('swf', 'svg', 'mp3')); //List of thumbless filetypes
		if(!isset($extArr[$image->ext])){
			$tsize = get_thumbnail_size($image->width, $image->height);
		}else{
			//Use max thumbnail size if using thumbless filetype
			$tsize = get_thumbnail_size($config->get_int('thumb_width'), $config->get_int('thumb_height'));
		}

		$custom_classes = "";
		if(class_exists("Relationships")){
			if(property_exists($image, 'parent_id') && $image->parent_id !== NULL){	$custom_classes .= "shm-thumb-has_parent ";	}
			if(property_exists($image, 'has_children') && $image->has_children == TRUE){ $custom_classes .= "shm-thumb-has_child "; }
		}

		return "<a href='$h_view_link' class='thumb shm-thumb shm-thumb-link {$custom_classes}' data-tags='$h_tags' data-post-id='$i_id'>".
				"<img id='thumb_$i_id' title='$h_tip' alt='$h_tip' height='{$tsize[1]}' width='{$tsize[0]}' src='$h_thumb_link'>".
				"</a>\n";
	}

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
		$body = $this->build_paginator($page_number, $total_pages, $base, $query, $show_random);
		$page->add_block(new Block(null, $body, "main", 90, "paginator"));
	}

	/**
	 * Generate a single HTML link.
	 *
	 * @param string $base_url
	 * @param string $query
	 * @param string $page
	 * @param string $name
	 * @return string
	 */
	private function gen_page_link($base_url, $query, $page, $name) {
		$link = make_link($base_url.'/'.$page, $query);
	    return '<a href="'.$link.'">'.$name.'</a>';
	}

	/**
	 * @param string $base_url
	 * @param string $query
	 * @param string $page
	 * @param int $current_page
	 * @param string $name
	 * @return string
	 */
	private function gen_page_link_block($base_url, $query, $page, $current_page, $name) {
		$paginator = "";
	    if($page == $current_page) $paginator .= "<b>";
	    $paginator .= $this->gen_page_link($base_url, $query, $page, $name);
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
	 * @param bool $show_random
	 * @return string
	 */
	private function build_paginator($current_page, $total_pages, $base_url, $query, $show_random) {
		$next = $current_page + 1;
		$prev = $current_page - 1;

		$at_start = ($current_page <= 1 || $total_pages <= 1);
		$at_end = ($current_page >= $total_pages);

		$first_html  = $at_start ? "First" : $this->gen_page_link($base_url, $query, 1,            "First");
		$prev_html   = $at_start ? "Prev"  : $this->gen_page_link($base_url, $query, $prev,        "Prev");

		$random_html = "-";
		if($show_random) {
			$rand = mt_rand(1, $total_pages);
			$random_html =                   $this->gen_page_link($base_url, $query, $rand,        "Random");
		}

		$next_html   = $at_end   ? "Next"  : $this->gen_page_link($base_url, $query, $next,        "Next");
		$last_html   = $at_end   ? "Last"  : $this->gen_page_link($base_url, $query, $total_pages, "Last");

		$start = $current_page-5 > 1 ? $current_page-5 : 1;
		$end = $start+10 < $total_pages ? $start+10 : $total_pages;

		$pages = array();
		foreach(range($start, $end) as $i) {
			$pages[] = $this->gen_page_link_block($base_url, $query, $i, $current_page, $i);
		}
		$pages_html = implode(" | ", $pages);

		return $first_html.' | '.$prev_html.' | '.$random_html.' | '.$next_html.' | '.$last_html
				.'<br>&lt;&lt; '.$pages_html.' &gt;&gt;';
	}
}

