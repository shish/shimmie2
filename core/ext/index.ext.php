<?php

class Index extends Extension {
// event handling {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "index")) {
			$search_terms = array();
			$page_number = 1;

			if($event->count_args() > 0) {
				$page_number = int_escape($event->get_arg(0));
				if($page_number == 0) $page_number = 1; // invalid -> 0
			}

			if(isset($_GET['search'])) {
				$search_terms = explode(' ', $_GET['search']);
				$query = "search=".url_escape($_GET['search']);
			}
			else {
				$query = null;
			}

			global $page;
			global $config;
			global $database;

			$total_pages = $database->count_pages($search_terms);
			$count = $config->get_int('index_width') * $config->get_int('index_height');
			$images = $database->get_images(($page_number-1)*$count, $count, $search_terms);

			if(count($search_terms) == 0) {
				$page_title = $config->get_string('title');
			}
			else {
				$page_title = html_escape($_GET['search']);
				/*
				$page_title = "";
				foreach($search_terms as $term) {
					$u_term = url_escape($term);
					$h_term = html_escape($term);
					$page_title .= "<a href='".make_link("post/list", "search=$u_term")."'>$h_term</a>";
				}
				*/
				if(count($images) > 0) {
					$page->set_subheading("Page $page_number / $total_pages");
				}
			}
			if($page_number > 1 || count($search_terms) > 0) {
				// $page_title .= " / $page_number";
			}
			
			$page->set_title($page_title);
			$page->set_heading($page_title);
			$page->add_block(new Block("Navigation", $this->build_navigation($page_number, $total_pages, $search_terms), "left", 0));
			if(count($images) > 0) {
				$page->add_block(new Block("Images", $this->build_table($images, $query), "main", 10));
				$page->add_block(new Paginator("index", $query, $page_number, $total_pages));
			}
			else {
				$page->add_block(new Block("No Images Found", "No images were found to match the search criteria"));
			}
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Index Options");
			$sb->position = 20;
			
			$sb->add_label("Index table size ");
			$sb->add_int_option("index_width");
			$sb->add_label(" x ");
			$sb->add_int_option("index_height");
			$sb->add_label(" images");

			$sb->add_text_option("image_tip", "<br>Image tooltip ");

			$event->panel->add_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_int_from_post("index_width");
			$event->config->set_int_from_post("index_height");
			$event->config->set_string_from_post("image_tip");
		}
	}
// }}}
// HTML generation {{{
	private function build_navigation($page_number, $total_pages, $search_terms) {
		$prev = $page_number - 1;
		$next = $page_number + 1;

		$u_tags = url_escape(implode(" ", $search_terms));
		$query = empty($u_tags) ? null : "search=$u_tags";

		
		$h_prev = ($page_number <= 1) ? "Prev" : "<a href='".make_link("index/$prev", $query)."'>Prev</a>";
		$h_index = "<a href='".make_link("index")."'>Index</a>";
		$h_next = ($page_number >= $total_pages) ? "Next" : "<a href='".make_link("index/$next", $query)."'>Next</a>";

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
// }}}
}
add_event_listener(new Index());
?>
