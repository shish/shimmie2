<?php

class TagList implements Extension {
	var $theme = null;
	
// event handling {{{
	public function receive_event(Event $event) {
		if($this->theme == null) $this->theme = get_theme_object("tag_list", "TagListTheme");
		
		if($event instanceof InitExtEvent) {
			global $config;
			$config->set_default_int("tag_list_length", 15);
			$config->set_default_int("tags_min", 3);
			$config->set_default_string("info_link", 'http://en.wikipedia.org/wiki/$tag');
			$config->set_default_string("tag_list_image_type", 'related');
		}

		if(($event instanceof PageRequestEvent) && ($event->page_name == "tags")) {
			global $page;

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
			}
			$this->theme->display_page($page);
		}

		if($event instanceof PostListBuildingEvent) {
			global $config;
			if($config->get_int('tag_list_length') > 0) {
				if(!empty($event->search_terms)) {
					$this->add_refine_block($event->page, $event->search_terms);
				}
				else {
					$this->add_popular_block($event->page);
				}
			}
		}

		if($event instanceof DisplayingImageEvent) {
			global $config;
			if($config->get_int('tag_list_length') > 0) {
				if($config->get_string('tag_list_image_type') == 'related') {
					$this->add_related_block($event->page, $event->image);
				}
				else {
					$this->add_tags_block($event->page, $event->image);
				}
			}
		}

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Tag Map Options");
			$sb->add_int_option("tags_min", "Only show tags used at least "); $sb->add_label(" times");
			$event->panel->add_block($sb);

			$sb = new SetupBlock("Popular / Related Tag List");
			$sb->add_int_option("tag_list_length", "Show top "); $sb->add_label(" tags");
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
// }}}
// maps {{{
	private function build_navigation() {
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_map = "<a href='".make_link("tags/map")."'>Map</a>";
		$h_alphabetic = "<a href='".make_link("tags/alphabetic")."'>Alphabetic</a>";
		$h_popularity = "<a href='".make_link("tags/popularity")."'>Popularity</a>";
		return "$h_index<br>$h_map<br>$h_alphabetic<br>$h_popularity";	
	}

	private function build_tag_map() {
		global $database;
		global $config;

		$tags_min = $config->get_int('tags_min');
		$result = $database->execute("
				SELECT
					tag,
					FLOOR(LOG(2.7, LOG(2.7, count - ? + 1)+1)*1.5*100)/100 AS scaled
				FROM tags
				WHERE count >= ?
				ORDER BY tag
			", array($tags_min, $tags_min));
		$tag_data = $result->GetArray();

		$html = "";
		foreach($tag_data as $row) {
			$h_tag = html_escape($row['tag']);
			$size = $row['scaled'];
			$link = $this->tag_link($row['tag']);
			$html .= "&nbsp;<a style='font-size: ${size}em' href='$link'>$h_tag</a>&nbsp;\n";
		}
		return $html;
	}

	private function build_tag_alphabetic() {
		global $database;
		global $config;

		$tags_min = $config->get_int('tags_min');
		$result = $database->execute(
				"SELECT tag,count FROM tags WHERE count >= ? ORDER BY tag",
				array($tags_min));
		$tag_data = $result->GetArray();

		$html = "";
		$lastLetter = 0;
		foreach($tag_data as $row) {
			$h_tag = html_escape($row['tag']);
			$count = $row['count'];
			if($lastLetter != strtolower(substr($h_tag, 0, 1))) {
				$lastLetter = strtolower(substr($h_tag, 0, 1));
				$html .= "<p>$lastLetter<br>";
			}
			$link = $this->tag_link($row['tag']);
			$html .= "<a href='$link'>$h_tag&nbsp;($count)</a>\n";
		}

		return $html;
	}

	private function build_tag_popularity() {
		global $database;
		global $config;

		$tags_min = $config->get_int('tags_min');
		$result = $database->execute(
				"SELECT tag,count,FLOOR(LOG(count)) AS scaled FROM tags WHERE count >= ? ORDER BY count DESC, tag ASC",
				array($tags_min));
		$tag_data = $result->GetArray();

		$html = "Results grouped by log<sub>e</sub>(n)";
		$lastLog = 0;
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

		return $html;
	}
// }}}
// blocks {{{
	private function add_related_block($page, $image) {
		global $database;
		global $config;

		$query = "
			SELECT COUNT(it3.image_id) as count, t3.tag AS tag 
			FROM
				image_tags AS it1,
				image_tags AS it2,
				image_tags AS it3,
				tags AS t1,
				tags AS t3
			WHERE
				it1.image_id=?
				AND it1.tag_id=it2.tag_id
				AND it2.image_id=it3.image_id
				AND t1.tag != 'tagme'
				AND t3.tag != 'tagme'
				AND t1.id = it1.tag_id
				AND t3.id = it3.tag_id
			GROUP BY it3.tag_id
			ORDER BY count DESC
			LIMIT ?
		";
		$args = array($image->id, $config->get_int('tag_list_length'));

		$tags = $database->get_all($query, $args);
		if(count($tags) > 0) {
			$this->theme->display_related_block($page, $tags);
		}
	}

	private function add_tags_block($page, $image) {
		global $database;
		global $config;

		$query = "
			SELECT tags.tag, tags.count
			FROM tags, image_tags
			WHERE tags.id = image_tags.tag_id
			AND image_tags.image_id = ?
			ORDER BY count DESC
			LIMIT ?
		";
		$args = array($image->id, $config->get_int('tag_list_length'));

		$tags = $database->get_all($query, $args);
		if(count($tags) > 0) {
			$this->theme->display_related_block($page, $tags);
		}
	}

	private function add_popular_block($page) {
		global $database;
		global $config;

		$query = "
			SELECT tag, count
			FROM tags
			ORDER BY count DESC
			LIMIT ?
		";
		$args = array($config->get_int('tag_list_length'));

		$tags = $database->get_all($query, $args);
		if(count($tags) > 0) {
			$this->theme->display_popular_block($page, $tags);
		}
	}

	private function add_refine_block($page, $search) {
		global $database;
		global $config;

		$wild_tags = tag_explode($search);
		// $search_tags = array();
		
		$tag_id_array = array();
		$tags_ok = true;
		foreach($wild_tags as $tag) {
			$tag = str_replace("*", "%", $tag);
			$tag = str_replace("?", "_", $tag);
			$tag_ids = $database->db->GetCol("SELECT id FROM tags WHERE tag LIKE ?", array($tag));
			// $search_tags = array_merge($search_tags,
			//                  $database->db->GetCol("SELECT tag FROM tags WHERE tag LIKE ?", array($tag)));
			$tag_id_array = array_merge($tag_id_array, $tag_ids);
			$tags_ok = count($tag_ids) > 0;
			if(!$tags_ok) break;
		}
		$tag_id_list = join(', ', $tag_id_array);

		if($tags_ok) {
			$query = "
				SELECT t2.tag AS tag, COUNT(it2.image_id) AS count
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
				ORDER BY count
				DESC LIMIT ?
			";
			$args = array($config->get_int('tag_list_length'));

			$related_tags = $database->get_all($query, $args);
			print $database->db->ErrorMsg();
			if(count($related_tags) > 0) {
				$this->theme->display_refine_block($page, $related_tags, $wild_tags);
			}
		}
	}
// }}}
}
add_event_listener(new TagList());
?>
