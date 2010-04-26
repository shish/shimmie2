<?php
/*
 * Name: [Beta] Shimmie JSON API
 * Author: Shish <webmaster@shishnet.org>
 * Description: A JSON interface to shimmie data [WARNING]
 * Documentation:
 *   <b>Admin Warning:</b> this exposes private data, eg IP addresses
 *   <p><b>Developer Warning:</b> the API is unstable; notably, private data may get hidden
 */

class ShimmieApi extends SimpleExtension {
	public function onPageRequest(PageRequestEvent $event) {
		global $database, $page;

		if($event->page_matches("api/shimmie")) {
			$page->set_mode("data");
			$page->set_type("text/plain");

			if($event->get_arg(0) == "get_tags") {
				if($event->count_args() == 2) {
					$all = $database->get_all(
						"SELECT tag FROM tags WHERE tag LIKE ?",
						array($event->get_arg(1)."%"));
				}
				else {
					$all = $database->get_all("SELECT tag FROM tags");
				}
				$res = array();
				foreach($all as $row) {$res[] = $row["tag"];}
				$page->set_data(json_encode($res));
			}

			if($event->get_arg(0) == "get_image") {
				$image = Image::by_id(int_escape($event->get_arg(1)));
				$image->get_tag_array(); // tag data isn't loaded into the object until necessary
				$page->set_data(json_encode($image));
			}

			if($event->get_arg(0) == "find_images") {
				$search_terms = $event->get_search_terms();
				$page_number = $event->get_page_number();
				$page_size = $event->get_page_size();
				$images = Image::find_images(($page_number-1)*$page_size, $page_size, $search_terms);
				foreach($images as $image) $image->get_tag_array();
				$page->set_data(json_encode($images));
			}
		}
	}
}
?>
