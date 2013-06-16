<?php
/**
 * Name: Image List
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Show a list of uploaded images
 * Documentation:
 *  Here is a list of the search methods available out of the box;
 *  Shimmie extensions may provide other filters:
 *  <ul>
 *    <li>by tag, eg
 *      <ul>
 *        <li>cat
 *        <li>pie
 *        <li>somethi* -- wildcards are supported
 *      </ul>
 *    <li>size (=, &lt;, &gt;, &lt;=, &gt;=) width x height, eg
 *      <ul>
 *        <li>size=1024x768 -- a specific wallpaper size
 *        <li>size&gt;=500x500 -- no small images
 *        <li>size&lt;1000x1000 -- no large images
 *      </ul>
 *    <li>ratio (=, &lt;, &gt;, &lt;=, &gt;=) width : height, eg
 *      <ul>
 *        <li>ratio=4:3, ratio=16:9 -- standard wallpaper
 *        <li>ratio=1:1 -- square images
 *        <li>ratio<1:1 -- tall images
 *        <li>ratio>1:1 -- wide images
 *      </ul>
 *    <li>filesize (=, &lt;, &gt;, &lt;=, &gt;=) size, eg
 *      <ul>
 *        <li>filesize&gt;1024 -- no images under 1KB
 *        <li>filesize&lt=3MB -- shorthand filesizes are supported too
 *      </ul>
 *    <li>id (=, &lt;, &gt;, &lt;=, &gt;=) number, eg
 *      <ul>
 *        <li>id<20 -- search only the first few images
 *        <li>id>=500 -- search later images
 *      </ul>
 *    <li>user=Username, eg
 *      <ul>
 *        <li>user=Shish -- find all of Shish's posts
 *      </ul>
 *    <li>hash=md5sum, eg
 *      <ul>
 *        <li>hash=bf5b59173f16b6937a4021713dbfaa72 -- find the "Taiga want up!" image
 *      </ul>
 *    <li>filetype=type, eg
 *      <ul>
 *        <li>filetype=png -- find all PNG images
 *      </ul>
 *    <li>filename=blah, eg
 *      <ul>
 *        <li>filename=kitten -- find all images with "kitten" in the original filename
 *      </ul>
 *    <li>posted (=, &lt;, &gt;, &lt;=, &gt;=) date, eg
 *      <ul>
 *        <li>posted&gt;=2009-12-25 posted&lt;=2010-01-01 -- find images posted between christmas and new year
 *      </ul>
 *  </ul>
 *  <p>Search items can be combined to search for images which match both,
 *  or you can stick "-" in front of an item to search for things that don't
 *  match it.
 *  <p>Some search methods provided by extensions:
 *  <ul>
 *    <li>Danbooru API
 *      <ul>
 *        <li>md5:[hash] -- same as "hash=", but the API calls it by a different name
 *      </ul>
 *    <li>Numeric Score
 *      <ul>
 *        <li>score (=, &lt;, &gt;, &lt;=, &gt;=) number -- seach by score
 *        <li>upvoted_by=Username -- search for a user's likes
 *        <li>downvoted_by=Username -- search for a user's dislikes
 *      </ul>
 *    <li>Image Rating
 *      <ul>
 *        <li>rating=se -- find safe and explicit images, ignore questionable and unknown
 *      </ul>
 *    <li>Favorites
 *      <ul>
 *        <li>favorites (=, &lt;, &gt;, &lt;=, &gt;=) number -- search for images favourited a certain number of times
 *        <li>favourited_by=Username -- search for a user's choices
 *      </ul>
 *    <li>Notes
 *      <ul>
 *        <li>notes (=, &lt;, &gt;, &lt;=, &gt;=) number -- search by the number of notes an image has
 *      </ul>
 *  </ul>
 */

/*
 * SearchTermParseEvent:
 * Signal that a search term needs parsing
 */
class SearchTermParseEvent extends Event {
	var $term = null;
	var $context = null;
	var $querylets = array();

	public function SearchTermParseEvent($term, $context) {
		$this->term = $term;
		$this->context = $context;
	}

	public function is_querylet_set() {
		return (count($this->querylets) > 0);
	}

	public function get_querylets() {
		return $this->querylets;
	}

	public function add_querylet($q) {
		$this->querylets[] = $q;
	}
}

class SearchTermParseException extends SCoreException {
}

class PostListBuildingEvent extends Event {
	var $search_terms = null;
	var $parts = array();

	public function __construct($search) {
		$this->search_terms = $search;
	}

	public function add_control(/*string*/ $html, /*int*/ $position=50) {
		while(isset($this->parts[$position])) $position++;
		$this->parts[$position] = $html;
	}
}

class Index extends Extension {
	var $stpen = 0;  // search term parse event number

	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_int("index_images", 24);
		$config->set_default_bool("index_tips", true);
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $database, $page, $user;
		if($event->page_matches("post/list")) {
			if(isset($_GET['search'])) {
				$search = url_escape(trim($_GET['search']));
				if(empty($search)) {
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/list/1"));
				}
				else {
					$page->set_mode("redirect");
					$page->set_redirect(make_link('post/list/'.$search.'/1'));
				}
				return;
			}

			$search_terms = $event->get_search_terms();
			$page_number = $event->get_page_number();
			$page_size = $event->get_page_size();
			try {
				$total_pages = Image::count_pages($search_terms);
				if(SPEED_HAX && count($search_terms) == 0 && ($page_number < 10)) { // extra caching for the first few post/list pages
					$images = $database->cache->get("post-list-$page_number");
					if(!$images) {
						$images = Image::find_images(($page_number-1)*$page_size, $page_size, $search_terms);
						$database->cache->set("post-list-$page_number", $images, 600);
					}
				}
				else {
					$images = Image::find_images(($page_number-1)*$page_size, $page_size, $search_terms);
				}
			}
			catch(SearchTermParseException $stpe) {
				// FIXME: display the error somewhere
				$total_pages = 0;
				$images = array();
			}

			if(count($search_terms) == 0 && count($images) == 0 && $page_number == 1) {
				$this->theme->display_intro($page);
				send_event(new PostListBuildingEvent($search_terms));
			}
			else if(count($search_terms) > 0 && count($images) == 1 && $page_number == 1) {
				$page->set_mode("redirect");
				$page->set_redirect(make_link('post/view/'.$images[0]->id));
			}
			else {
				$plbe = new PostListBuildingEvent($search_terms);
				send_event($plbe);

				$this->theme->set_page($page_number, $total_pages, $search_terms);
				$this->theme->display_page($page, $images);
				if(count($plbe->parts) > 0) {
					$this->theme->display_admin_block($plbe->parts);
				}
			}
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Index Options");
		$sb->position = 20;

		$sb->add_label("Show ");
		$sb->add_int_option("index_images");
		$sb->add_label(" images on the post list");

		$event->panel->add_block($sb);
	}

	public function onImageAddition(ImageAdditionEvent $event) {
		global $database;
		if(SPEED_HAX) {
			for($i=1; $i<10; $i++) {
				$database->cache->delete("post-list-$i");
			}
		}
	}

	public function onImageDeletion(ImageDeletionEvent $event) {
		global $database;
		if(SPEED_HAX) {
			for($i=1; $i<10; $i++) {
				$database->cache->delete("post-list-$i");
			}
		}
	}

	public function onSearchTermParse(SearchTermParseEvent $event) {
		$matches = array();
		// check for tags first as tag based searches are more common.
		if(preg_match("/tags(<|>|<=|>=|=)(\d+)/", $event->term, $matches)) {
			$cmp = $matches[1];
			$tags = $matches[2];
			$event->add_querylet(new Querylet('images.id IN (SELECT DISTINCT image_id FROM image_tags GROUP BY image_id HAVING count(image_id) '.$cmp.' '.$tags.')'));
		}
		else if(preg_match("/^ratio(<|>|<=|>=|=)(\d+):(\d+)$/", $event->term, $matches)) {
			$cmp = $matches[1];
			$args = array("width{$this->stpen}"=>int_escape($matches[2]), "height{$this->stpen}"=>int_escape($matches[3]));
			$event->add_querylet(new Querylet("width / height $cmp :width{$this->stpen} / :height{$this->stpen}", $args));
		}
		else if(preg_match("/^(filesize|id)(<|>|<=|>=|=)(\d+[kmg]?b?)$/i", $event->term, $matches)) {
			$col = $matches[1];
			$cmp = $matches[2];
			$val = parse_shorthand_int($matches[3]);
			$event->add_querylet(new Querylet("images.$col $cmp :val{$this->stpen}", array("val{$this->stpen}"=>$val)));
		}
		else if(preg_match("/^(hash|md5)=([0-9a-fA-F]*)$/i", $event->term, $matches)) {
			$hash = strtolower($matches[2]);
			$event->add_querylet(new Querylet('images.hash = :hash', array("hash" => $hash)));
		}
		else if(preg_match("/^(filetype|ext)=([a-zA-Z0-9]*)$/i", $event->term, $matches)) {
			$ext = strtolower($matches[2]);
			$event->add_querylet(new Querylet('images.ext = :ext', array("ext" => $ext)));
		}
		else if(preg_match("/^(filename|name)=([a-zA-Z0-9]*)$/i", $event->term, $matches)) {
			$filename = strtolower($matches[2]);
			$event->add_querylet(new Querylet("images.filename LIKE :filename{$this->stpen}", array("filename{$this->stpen}"=>"%$filename%")));
		}
		else if(preg_match("/^(source)=([a-zA-Z0-9]*)$/i", $event->term, $matches)) {
			$filename = strtolower($matches[2]);
			$event->add_querylet(new Querylet('images.source LIKE :src', array("src"=>"%$filename%")));
		}
		else if(preg_match("/^posted(<|>|<=|>=|=)([0-9-]*)$/", $event->term, $matches)) {
			$cmp = $matches[1];
			$val = $matches[2];
			$event->add_querylet(new Querylet("images.posted $cmp :posted{$this->stpen}", array("posted{$this->stpen}"=>$val)));
		}
		else if(preg_match("/^size(<|>|<=|>=|=)(\d+)x(\d+)$/", $event->term, $matches)) {
			$cmp = $matches[1];
			$args = array("width{$this->stpen}"=>int_escape($matches[2]), "height{$this->stpen}"=>int_escape($matches[3]));
			$event->add_querylet(new Querylet("width $cmp :width{$this->stpen} AND height $cmp :height{$this->stpen}", $args));
		}

		$this->stpen++;
	}
}
?>
