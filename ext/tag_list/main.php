<?php
/*
 * Name: Tag List
 * Author: Shish
 * Description: Show the tags in various ways
 */

class TagList implements Extension {
	var $theme = null;

// event handling {{{
	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if($this->theme == null) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			$config->set_default_int("tag_list_length", 15);
			$config->set_default_int("popular_tag_list_length", 15);
			$config->set_default_int("tags_min", 3);
			$config->set_default_string("info_link", 'http://en.wikipedia.org/wiki/$tag');
			$config->set_default_string("tag_list_image_type", 'related');
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("tags")) {
			$this->theme->set_navigation($this->build_navigation());
			switch($event->get_arg(0)) {
				default:
				case 'map':
					$this->theme->set_heading("Tag Map");
					$this->theme->set_tag_list($this->build_tag_map());
					break;
				case 'alphabetic':
					$this->theme->set_heading("Alphabetic Tag List");
					$this->theme->set_tag_list($this->build_tag_alphabetic());
					break;
				case 'popularity':
					$this->theme->set_heading("Tag List by Popularity");
					$this->theme->set_tag_list($this->build_tag_popularity());
					break;
				case 'categories':
					$this->theme->set_heading("Popular Categories");
					$this->theme->set_tag_list($this->build_tag_categories());
					break;
			}
			$this->theme->display_page($page);
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("api/internal/tag_list/complete")) {
			$all = $database->get_all(
					"SELECT tag FROM tags WHERE tag LIKE :search AND count > 0 LIMIT 10",
					array("search"=>$_GET["s"]."%"));

			$res = array();
			foreach($all as $row) {$res[] = $row["tag"];}

			$page->set_mode("data");
			$page->set_type("text/plain");
			$page->set_data(implode("\n", $res));
		}

		if($event instanceof PostListBuildingEvent) {
			if($config->get_int('tag_list_length') > 0) {
				if(!empty($event->search_terms)) {
					$this->add_refine_block($page, $event->search_terms);
				}
				else {
					$this->add_popular_block($page);
				}
			}
		}

		if($event instanceof DisplayingImageEvent) {
			if($config->get_int('tag_list_length') > 0) {
				if($config->get_string('tag_list_image_type') == 'related') {
					$this->add_related_block($page, $event->image);
				}
				else {
					$this->add_tags_block($page, $event->image);
				}
			}
		}

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Tag Map Options");
			$sb->add_int_option("tags_min", "Only show tags used at least "); $sb->add_label(" times");
			$event->panel->add_block($sb);

			$sb = new SetupBlock("Popular / Related Tag List");
			$sb->add_int_option("tag_list_length", "Show top "); $sb->add_label(" related tags");
			$sb->add_int_option("popular_tag_list_length", "<br>Show top "); $sb->add_label(" popular tags");
			$sb->add_text_option("info_link", "<br>Tag info link: ");
			$sb->add_choice_option("tag_list_image_type", array(
				"Image's tags only" => "tags",
				"Show related" => "related"
			), "<br>Image tag list: ");
			$sb->add_bool_option("tag_list_numbers", "<br>Show tag counts: ");
			$event->panel->add_block($sb);
		}
	}
// }}}
// misc {{{
	private function tag_link($tag) {
		$u_tag = url_escape($tag);
		return make_link("post/list/$u_tag/1");
	}

	private function get_tags_min() {
		if(isset($_GET['mincount'])) {
			return int_escape($_GET['mincount']);
		}
		else {
			global $config;
			return $config->get_int('tags_min');
		}
	}
// }}}
// maps {{{
	private function build_navigation() {
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_map = "<a href='".make_link("tags/map")."'>Map</a>";
		$h_alphabetic = "<a href='".make_link("tags/alphabetic")."'>Alphabetic</a>";
		$h_popularity = "<a href='".make_link("tags/popularity")."'>Popularity</a>";
		$h_cats = "<a href='".make_link("tags/categories")."'>Categories</a>";
		$h_all = "<a href='?mincount=1'>Show All</a>";
		return "$h_index<br>&nbsp;<br>$h_map<br>$h_alphabetic<br>$h_popularity<br>$h_cats<br>&nbsp;<br>$h_all";
	}

	private function build_tag_map() {
		global $database;

		$tags_min = $this->get_tags_min();
		$starts_with = @$_GET['starts_with'] || "a";
		$cache_key = "data/tag_cloud-"+md5($tags_min + $starts_with)+".html";
		if(file_exists($cache_key)) {return file_get_contents($cache_key);}

		$tag_data = $database->get_all("
				SELECT
					tag,
					FLOOR(LOG(2.7, LOG(2.7, count - :tags_min + 1)+1)*1.5*100)/100 AS scaled
				FROM tags
				WHERE count >= :tags_min
				AND tag ILIKE :starts_with
				ORDER BY tag
			", array("tags_min"=>$tags_min, "starts_with"=>$starts_with+"%"));

		$html = "";
		foreach($tag_data as $row) {
			$h_tag = html_escape($row['tag']);
			$size = sprintf("%.2f", (float)$row['scaled']);
			$link = $this->tag_link($row['tag']);
			if($size<0.5) $size = 0.5;
			$h_tag_no_underscores = str_replace("_", " ", $h_tag);
			$html .= "&nbsp;<a style='font-size: ${size}em' href='$link'>$h_tag_no_underscores</a>&nbsp;\n";
		}

		if(SPEED_HAX) {file_put_contents($cache_key, $html);}

		return $html;
	}

	private function build_tag_alphabetic() {
		global $database;

		$tags_min = $this->get_tags_min();
		$starts_with = @$_GET['starts_with'] || "a";
		$cache_key = "data/tag_alpha-"+md5($tags_min + $starts_with)+".html";
		if(file_exists($cache_key)) {return file_get_contents($cache_key);}

		$tag_data = $database->get_all("
				SELECT tag, count
				FROM tags
				WHERE count >= :tags_min
				AND tag ILIKE :starts_with
				ORDER BY tag
				", array("tags_min"=>$tags_min, "starts_with"=>$starts_with+"%"));

		$html = "";
		$lastLetter = "";
		foreach($tag_data as $row) {
			$h_tag = html_escape($row['tag']);
			$count = $row['count'];
			if($lastLetter != strtolower(substr($h_tag, 0, count($starts_with)+1))) {
				$lastLetter = strtolower(substr($h_tag, 0, count($starts_with)+1));
				$html .= "<p>$lastLetter<br>";
			}
			$link = $this->tag_link($row['tag']);
			$html .= "<a href='$link'>$h_tag&nbsp;($count)</a>\n";
		}

		if(SPEED_HAX) {file_put_contents($cache_key, $html);}

		return $html;
	}

	private function build_tag_popularity() {
		global $database;

		$tags_min = $this->get_tags_min();
		$cache_key = "data/tag_popul-"+md5($tags_min)+".html";
		if(file_exists($cache_key)) {return file_get_contents($cache_key);}

		$tag_data = $database->get_all("
				SELECT tag, count, FLOOR(LOG(count)) AS scaled
				FROM tags
				WHERE count >= :tags_min
				ORDER BY count DESC, tag ASC
				", array("tags_min"=>$tags_min));

		$html = "Results grouped by log<sub>e</sub>(n)";
		$lastLog = "";
		foreach($tag_data as $row) {
			$h_tag = html_escape($row['tag']);
			$count = $row['count'];
			$scaled = $row['scaled'];
			if($lastLog != $scaled) {
				$lastLog = $scaled;
				$html .= "<p>$lastLog<br>";
			}
			$link = $this->tag_link($row['tag']);
			$html .= "<a href='$link'>$h_tag&nbsp;($count)</a>\n";
		}

		if(SPEED_HAX) {file_put_contents($cache_key, $html);}

		return $html;
	}

	private function build_tag_categories() {
		global $database;

		$tags_min = $this->get_tags_min();
		$tag_data = $database->get_all("SELECT tag,count FROM tags ORDER BY count DESC, tag ASC LIMIT 9");

		$html = "<table>";
		$n = 0;
		foreach($tag_data as $row) {
			if($n%3==0) $html .= "<tr>";
			$h_tag = html_escape($row['tag']);
			$link = $this->tag_link($row['tag']);
			$image = Image::by_random(array($row['tag']));
			if(is_null($image)) continue; // one of the popular tags has no images
			$thumb = $image->get_thumb_link();
			$tsize = get_thumbnail_size($image->width, $image->height);
			$html .= "<td><a href='$link'><img src='$thumb' style='height: {$tsize[1]}px; width: {$tsize[0]}px;'><br>$h_tag</a></td>\n";
			if($n%3==2) $html .= "</tr>";
			$n++;
		}
		$html .= "</table>";

		return $html;
	}
// }}}
// blocks {{{
	private function add_related_block($page, $image) {
		global $database;
		global $config;

		$query = "
			SELECT t3.tag AS tag, t3.count AS calc_count
			FROM
				image_tags AS it1,
				image_tags AS it2,
				image_tags AS it3,
				tags AS t1,
				tags AS t3
			WHERE
				it1.image_id=:image_id
				AND it1.tag_id=it2.tag_id
				AND it2.image_id=it3.image_id
				AND t1.tag != 'tagme'
				AND t3.tag != 'tagme'
				AND t1.id = it1.tag_id
				AND t3.id = it3.tag_id
			GROUP BY it3.tag_id
			ORDER BY calc_count DESC
			LIMIT :tag_list_length
		";
		$args = array("image_id"=>$image->id, "tag_list_length"=>$config->get_int('tag_list_length'));

		$tags = $database->get_all($query, $args);
		if(count($tags) > 0) {
			$this->theme->display_related_block($page, $tags);
		}
	}

	private function add_tags_block($page, $image) {
		global $database;
		global $config;

		$query = "
			SELECT tags.tag, tags.count as calc_count
			FROM tags, image_tags
			WHERE tags.id = image_tags.tag_id
			AND image_tags.image_id = :image_id
			ORDER BY calc_count DESC
		";
		$args = array("image_id"=>$image->id);

		$tags = $database->get_all($query, $args);
		if(count($tags) > 0) {
			$this->theme->display_related_block($page, $tags);
		}
	}

	private function add_popular_block($page) {
		global $database;
		global $config;

		$tags = $database->cache->get("popular_tags");
		if(empty($tags)) {
			$query = "
				SELECT tag, count
				FROM tags
				WHERE count > 0
				ORDER BY count DESC
				LIMIT :popular_tag_list_length
				";
			$args = array("popular_tag_list_length"=>$config->get_int('popular_tag_list_length'));

			$tags = $database->get_all($query, $args);
			$database->cache->set("popular_tags", $tags, 600);
		}
		if(count($tags) > 0) {
			$this->theme->display_popular_block($page, $tags);
		}
	}

	private function add_refine_block($page, $search) {
		global $database;
		global $config;

		$wild_tags = Tag::explode($search);
		// $search_tags = array();

		$tag_id_array = array();
		$tags_ok = true;
		foreach($wild_tags as $tag) {
			$tag = str_replace("*", "%", $tag);
			$tag = str_replace("?", "_", $tag);
			$tag_ids = $database->get_col("SELECT id FROM tags WHERE tag LIKE :tag", array("tag"=>$tag));
			// $search_tags = array_merge($search_tags,
			//                  $database->get_col("SELECT tag FROM tags WHERE tag LIKE :tag", array("tag"=>$tag)));
			$tag_id_array = array_merge($tag_id_array, $tag_ids);
			$tags_ok = count($tag_ids) > 0;
			if(!$tags_ok) break;
		}
		$tag_id_list = join(', ', $tag_id_array);

		if($tags_ok) {
			$query = "
				SELECT t2.tag AS tag, COUNT(it2.image_id) AS calc_count
				FROM
					image_tags AS it1,
					image_tags AS it2,
					tags AS t1,
					tags AS t2
				WHERE
					t1.id IN($tag_id_list)
					AND it1.image_id=it2.image_id
					AND it1.tag_id = t1.id
					AND it2.tag_id = t2.id
				GROUP BY t2.tag
				ORDER BY calc_count
				DESC LIMIT :limit
			";
			$args = array("limit"=>$config->get_int('tag_list_length'));

			$related_tags = $database->get_all($query, $args);
			if(count($related_tags) > 0) {
				$this->theme->display_refine_block($page, $related_tags, $wild_tags);
			}
		}
	}
// }}}
}
add_event_listener(new TagList());
?>
