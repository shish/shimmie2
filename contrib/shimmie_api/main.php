<?php
/*
 * Name: [Beta] Shimmie JSON API
 * Author: Shish <webmaster@shishnet.org>
 * Description: A JSON interface to shimmie data [WARNING]
 * Documentation:
 *   <b>Admin Warning:</b> this exposes private data, eg IP addresses
 *   <p><b>Developer Warning:</b> the API is unstable; notably, private data may get hidden
 */


class _SafeImage {
#{"id":"2","height":"768","width":"1024","hash":"71cdfaabbcdad3f777e0b60418532e94","filesize":"439561","filename":"HeilAmu.png","ext":"png","owner_ip":"0.0.0.0","posted":"0000-00-00 00:00:00","source":null,"locked":"N","owner_id":"0","rating":"u","numeric_score":"0","text_score":"0","notes":"0","favorites":"0","posted_timestamp":-62169955200,"tag_array":["cat","kunimitsu"]}

	function __construct(Image $img) {
		$this->id       = $img->id;
		$this->height   = $img->height;
		$this->width    = $img->width;
		$this->hash     = $img->hash;
		$this->filesize = $img->filesize;
		$this->ext      = $img->ext;
		$this->posted   = $img->posted_timestamp;
		$this->source   = $img->source;
		$this->owner_id = $img->owner_id;
		$this->tags     = $img->tag_array;
	}
}

class ShimmieApi extends SimpleExtension {
	public function onPageRequest(PageRequestEvent $event) {
		global $database, $page;

		if($event->page_matches("api/shimmie")) {
			$page->set_mode("data");
			$page->set_type("text/plain");

			if($event->page_matches("api/shimmie/get_tags")) {
				if($event->count_args() == 2) {
					$all = $database->get_all(
						"SELECT tag FROM tags WHERE tag LIKE ?",
						array($event->get_arg(0)."%"));
				}
				else {
					$all = $database->get_all("SELECT tag FROM tags");
				}
				$res = array();
				foreach($all as $row) {$res[] = $row["tag"];}
				$page->set_data(json_encode($res));
			}

			if($event->page_matches("api/shimmie/get_image")) {
				$image = Image::by_id(int_escape($event->get_arg(0)));
				$image->get_tag_array(); // tag data isn't loaded into the object until necessary
				$safe_image = new _SafeImage($image);
				$page->set_data(json_encode($safe_image));
			}

			if($event->page_matches("api/shimmie/find_images")) {
				$search_terms = $event->get_search_terms();
				$page_number = $event->get_page_number();
				$page_size = $event->get_page_size();
				$images = Image::find_images(($page_number-1)*$page_size, $page_size, $search_terms);
				$safe_images = array();
				foreach($images as $image) {
					$image->get_tag_array();
					$safe_images[] = new _SafeImage($image);
				}
				$page->set_data(json_encode($safe_images));
			}
		}
	}
}
?>
