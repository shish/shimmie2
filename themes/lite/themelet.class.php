<?php
class Themelet extends BaseThemelet {
	/**
	 * Generic thumbnail code; returns HTML rather than adding
	 * a block since thumbs tend to go inside blocks...
	 */
	public function build_thumb_html(Image $image, $query=null) {
		global $config;
		$i_id = (int) $image->id;
		$h_view_link = make_link('post/view/'.$i_id, $query);
		$h_thumb_link = $image->get_thumb_link();
		$h_tip = html_escape($image->get_tooltip());
		$h_tags = strtolower($image->get_tag_list());
		$base = get_base_href();

		// If file is flash or svg then sets thumbnail to max size.
		if($image->ext === 'swf' || $image->ext === 'svg'){
			$tsize = get_thumbnail_size($config->get_int('thumb_width'), $config->get_int('thumb_height'));
		}
		else{
			$tsize = get_thumbnail_size($image->width, $image->height);
		}

		return '<center><div class="thumbblock">'.
		       '<a href="'.$h_view_link.'" class="thumb" data-tags="'.$h_tags.'">'.
		       '<img id="thumb_'.$i_id.'" title="'.$h_tip.'" alt="'.$h_tip.'" height="'.$tsize[1].'" width="'.$tsize[0].'" class="lazy" data-original="'.$h_thumb_link.'" src="'.$base.'/lib/static/grey.gif">'.
		       '<noscript><img id="thumb_'.$i_id.'" title="'.$h_tip.'" alt="'.$h_tip.'" height="'.$tsize[1].'" width="'.$tsize[0].'" src="'.$h_thumb_link.'"></noscript>'.
			   "</a></div></center>\n";
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
