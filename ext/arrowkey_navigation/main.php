<?php
/**
 * Name: Arrow Key Navigation
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://www.drudexsoftware.com/
 * License: GPLv2
 * Description: Allows viewers no navigate between images using the left & right arrow keys.
 * Documentation:
 *  Simply enable this extention in the extention manager to enable arrow key navigation.
 */
class ArrowkeyNavigation extends Extension {
	/**
	 * Adds functionality for post/view on images.
	 *
	 * @param DisplayingImageEvent $event
	 */
	public function onDisplayingImage(DisplayingImageEvent $event) {
		$prev_url = make_http(make_link("post/prev/".$event->image->id));
		$next_url = make_http(make_link("post/next/".$event->image->id));
		$this->add_arrowkeys_code($prev_url, $next_url);
	}

	/**
	 * Adds functionality for post/list.
	 *
	 * @param PageRequestEvent $event
	 */
	public function onPageRequest(PageRequestEvent $event) {
		if($event->page_matches("post/list")) {
			$pageinfo = $this->get_list_pageinfo($event);
			$prev_url = make_http(make_link("post/list/".$pageinfo["prev"]));
			$next_url = make_http(make_link("post/list/".$pageinfo["next"]));
			$this->add_arrowkeys_code($prev_url, $next_url);
		}
	}

	/**
	 * Adds the javascript to the page with the given urls.
	 *
	 * @param string $prev_url
	 * @param string $next_url
	 */
	private function add_arrowkeys_code($prev_url, $next_url) {
		global $page;

		$page->add_html_header("<script type=\"text/javascript\">
			(function($){
				$(document).keyup(function(e) {
					if($(e.target).is('input', 'textarea')){ return; }
					if (e.keyCode == 37) { window.location.href = '{$prev_url}'; }
					else if (e.keyCode == 39) { window.location.href = '{$next_url}'; }
				});
			})(jQuery);
			</script>", 60);
	}

	/**
	 * Returns info about the current page number.
	 *
	 * @param PageRequestEvent $event
	 * @return array
	 */
	private function get_list_pageinfo(PageRequestEvent $event) {
		global $config, $database;

		// get the amount of images per page
		$images_per_page = $config->get_int('index_images');

		// if there are no tags, use default
		if (is_null($event->get_arg(1))){
			$prefix = "";
			$page_number = int_escape($event->get_arg(0));
			$total_pages = ceil($database->get_one(
				"SELECT COUNT(*) FROM images") / $images_per_page);
		}
		else { // if there are tags, use pages with tags
			$prefix = url_escape($event->get_arg(0)) . "/";
			$page_number = int_escape($event->get_arg(1));
			$total_pages = ceil($database->get_one(
				"SELECT count FROM tags WHERE tag=:tag",
					array("tag"=>$event->get_arg(0))) / $images_per_page);
		}

		// creates previous & next values
		// When previous first page, go to last page
		if ($page_number <= 1) $prev = $total_pages;
		else $prev = $page_number-1;
		if ($page_number >= $total_pages) $next = 1;
		else $next = $page_number+1;

		// Create return array
		$pageinfo = array(
			"prev" => $prefix.$prev,
			"next" => $prefix.$next,
		);

		return $pageinfo;
	}
}

