<?php
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

class PostListBuildingEvent extends Event {
	var $page = null;
	var $search_terms = null;

	public function PostListBuildingEvent($page, $search) {
		$this->page = $page;
		$this->search_terms = $search;
	}
}

class Index implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			global $config;
			$config->set_default_int("index_width", 3);
			$config->set_default_int("index_height", 4);
			$config->set_default_bool("index_tips", true);
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("post/list")) {
			if(isset($_GET['search'])) {
				$search = url_escape(trim($_GET['search']));
				if(empty($search)) {
					$event->page->set_mode("redirect");
					$event->page->set_redirect(make_link("post/list/1"));
				}
				else {
					$event->page->set_mode("redirect");
					$event->page->set_redirect(make_link("post/list/$search/1"));
				}
				return;
			}

			$search_terms = array();
			$page_number = 1;

			if($event->count_args() == 1) {
				$page_number = int_escape($event->get_arg(0));
			}
			else if($event->count_args() == 2) {
				$search_terms = explode(' ', $event->get_arg(0));
				$page_number = int_escape($event->get_arg(1));
			}

			if($page_number == 0) $page_number = 1; // invalid -> 0

			global $config;
			global $database;

			$total_pages = Image::count_pages($config, $database, $search_terms);
			$count = $config->get_int('index_width') * $config->get_int('index_height');
			$images = Image::find_images($config, $database, ($page_number-1)*$count, $count, $search_terms);

			send_event(new PostListBuildingEvent($event->page, $search_terms));

			$this->theme->set_page($page_number, $total_pages, $search_terms);
			$this->theme->display_page($event->page, $images);
		}

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Index Options");
			$sb->position = 20;

			$sb->add_label("Index table size ");
			$sb->add_int_option("index_width");
			$sb->add_label(" x ");
			$sb->add_int_option("index_height");
			$sb->add_label(" images");

			$event->panel->add_block($sb);
		}

		if($event instanceof SearchTermParseEvent) {
			$matches = array();
			if(preg_match("/size(<|>|<=|>=|=)(\d+)x(\d+)/", $event->term, $matches)) {
				$cmp = $matches[1];
				$args = array(int_escape($matches[2]), int_escape($matches[3]));
				$event->add_querylet(new Querylet("width $cmp ? AND height $cmp ?", $args));
			}
			else if(preg_match("/ratio(<|>|<=|>=|=)(\d+):(\d+)/", $event->term, $matches)) {
				$cmp = $matches[1];
				$args = array(int_escape($matches[2]), int_escape($matches[3]));
				$event->add_querylet(new Querylet("width / height $cmp ? / ?", $args));
			}
			else if(preg_match("/(filesize|id)(<|>|<=|>=|=)(\d+[kmg]?b?)/i", $event->term, $matches)) {
				$col = $matches[1];
				$cmp = $matches[2];
				$val = parse_shorthand_int($matches[3]);
				$event->add_querylet(new Querylet("images.$col $cmp ?", array($val)));
			}
			else if(preg_match("/hash=([0-9a-fA-F]*)/i", $event->term, $matches)) {
				$hash = strtolower($matches[2]);
				$event->add_querylet(new Querylet("images.hash = '$hash'"));
			}
			else if(preg_match("/(filetype|ext)=([a-zA-Z0-9]*)/i", $event->term, $matches)) {
				$ext = strtolower($matches[2]);
				$event->add_querylet(new Querylet("images.ext = '$ext'"));
			}
			else if(preg_match("/(filename|name)=([a-zA-Z0-9]*)/i", $event->term, $matches)) {
				$filename = strtolower($matches[2]);
				$event->add_querylet(new Querylet("images.filename LIKE '%$filename%'"));
			}
			else if(preg_match("/posted=(([0-9\*]*)?(-[0-9\*]*)?(-[0-9\*]*)?)/", $event->term, $matches)) {
				$val = str_replace("*", "%", $matches[1]);
				$img_search->append(new Querylet("images.posted LIKE '%$val%'"));
			}
		}
	}
}
add_event_listener(new Index());
?>
