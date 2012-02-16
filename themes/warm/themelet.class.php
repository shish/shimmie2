<?php
/**
 * A collection of common functions for theme parts
 */
class Themelet {
	/**
	 * Generic error message display
	 */
	public function display_error(/*int*/ $code, /*string*/ $title, /*string*/ $message) {
		global $page;
		$page->add_http_header("HTTP/1.0 $code $title");
		$page->set_title($title);
		$page->set_heading($title);
		$page->add_block(new NavBlock());
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
	 */
	public function build_thumb_html(Image $image, $query=null) {
		global $config;
		$i_id = int_escape($image->id);
		$h_view_link = make_link("post/view/$i_id", $query);
		$h_image_link = $image->get_image_link();
		$h_thumb_link = $image->get_thumb_link();


		// Removes the size tag if the file is an mp3 
		if($image->ext == 'mp3'){
			$iitip = $image->get_tooltip();
			$mp3tip = array("0x0");
			$h_tip = str_replace($mp3tip, " ", $iitip);
		
			// Makes it work with a variation of the default tooltips (I.E $tags // $filesize // $size)
			$justincase = array("   //", "//   ", "  //", "//  ", "  ");
				if(strstr($h_tip, "  ")){
					$h_tip = html_escape(str_replace($justincase, "", $h_tip));
				}else{
				$h_tip = html_escape($h_tip);
				}
		}else{
		$h_tip = html_escape($image->get_tooltip());
		}
		

		// If file is flash or svg then sets thumbnail to max size.
		if($image->ext == 'swf' || $image->ext == 'svg'){
		$tsize = get_thumbnail_size($config->get_int('thumb_width'), $config->get_int('thumb_height')); }
		else{
		$tsize = get_thumbnail_size($image->width, $image->height); }

		return "
			<div class='thumbblock'>
			<div class='rr thumb'>
				<div class='rrtop'><div></div></div>
				<div class='rrcontent'>
				<a href='$h_view_link' style='position: relative; display: block; height: {$tsize[1]}px; width: {$tsize[0]}px;'>
					<img id='thumb_$i_id' title='$h_tip' alt='$h_tip' style='height: {$tsize[1]}px; width: {$tsize[0]}px;' src='$h_thumb_link'>
				</a>
				</div>
				<div class='rrbot'><div></div></div>
			</div>
			</div>
		";
	}


	/**
	 * Put something in a box; specific to the default theme
	 */
	public function box($html) {
		return "
			<div class='rr'>
				<div class='rrtop'><div></div></div>
				<div class='rrcontent'>$html</div>
				<div class='rrbot'><div></div></div>
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

	private function gen_page_link($base_url, $query, $page, $name) {
		$link = make_link("$base_url/$page", $query);
	    return "<a href='$link'>$name</a>";
	}
	
	private function gen_page_link_block($base_url, $query, $page, $current_page, $name) {
		$paginator = "";
	    if($page == $current_page) $paginator .= "<b>";
	    $paginator .= $this->gen_page_link($base_url, $query, $page, $name);
	    if($page == $current_page) $paginator .= "</b>";
	    return $paginator;
	}
					
	private function build_paginator($current_page, $total_pages, $base_url, $query) {
		$next = $current_page + 1;
		$prev = $current_page - 1;
		$rand = rand(1, $total_pages);

		$at_start = ($current_page <= 1 || $total_pages <= 1);
		$at_end = ($current_page >= $total_pages);

		$first_html  = $at_start ? "First" : $this->gen_page_link($base_url, $query, 1,            "First");
		$prev_html   = $at_start ? "Prev"  : $this->gen_page_link($base_url, $query, $prev,        "Prev");
		$random_html =                       $this->gen_page_link($base_url, $query, $rand,        "Random");
		$next_html   = $at_end   ? "Next"  : $this->gen_page_link($base_url, $query, $next,        "Next");
		$last_html   = $at_end   ? "Last"  : $this->gen_page_link($base_url, $query, $total_pages, "Last");

		$start = $current_page-5 > 1 ? $current_page-5 : 1;
		$end = $start+10 < $total_pages ? $start+10 : $total_pages;

		$pages = array();
		foreach(range($start, $end) as $i) {
			$pages[] = $this->gen_page_link_block($base_url, $query, $i, $current_page, $i);
		}
		$pages_html = implode(" | ", $pages);

		return "<p class='paginator'>$first_html | $prev_html | $random_html | $next_html | $last_html".
				"<br>&lt;&lt; $pages_html &gt;&gt;</p><!-- cancel border -->";
	}
}
?>
