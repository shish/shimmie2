<?php

class TagList extends Extension {
	var $theme = null;
	
// event handling {{{
	public function receive_event($event) {
		if($this->theme == null) $this->theme = get_theme_object("tag_list", "TagListTheme");
		
		if(is_a($event, 'InitExtEvent')) {
			global $config;
			$config->set_default("tag_list_length", 15);
			$config->set_default("tags_min", 3);
		}

		if(is_a($event, 'PageRequestEvent') && ($event->page == "tags")) {
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
		if(is_a($event, 'PageRequestEvent') && ($event->page == "index")) {
			global $config;
			global $page;
			if($config->get_int('tag_list_length') > 0) {
				if(isset($_GET['search'])) {
					$this->add_refine_block($page, tag_explode($_GET['search']));
				}
				else {
					$this->add_popular_block($page);
				}
			}
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			global $config;
			if($config->get_int('tag_list_length') > 0) {
				$this->add_related_block($event->page, $event->image);
			}
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Tag Map Options");
			$sb->add_int_option("tags_min", "Ignore tags used fewer than "); $sb->add_label(" times");
			$event->panel->add_block($sb);

			$sb = new SetupBlock("Popular / Related Tag List");
			$sb->add_int_option("tag_list_length", "Show top "); $sb->add_label(" tags");
			$sb->add_text_option("info_link", "<br>Tag info link: ");
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
		$h_index = "<a href='".make_link("index")."'>Index</a>";
		$h_map = "<a href='".make_link("tags/map")."'>Map</a>";
		$h_alphabetic = "<a href='".make_link("tags/alphabetic")."'>Alphabetic</a>";
		$h_popularity = "<a href='".make_link("tags/popularity")."'>Popularity</a>";
		return "$h_index<br>$h_map<br>$h_alphabetic<br>$h_popularity";	
	}

	private function build_tag_map() {
		global $database;
		global $config;

		$tags_min = $config->get_int('tags_min');
		$result = $database->Execute(
				"SELECT tag,count FROM tags WHERE count > ? ORDER BY tag",
				array($tags_min));

		$html = "";
		while(!$result->EOF) {
			$row = $result->fields;
			$h_tag = html_escape($row['tag']);
			$count = $row['count'];
			if($count > 1) {
				$size = floor(log(log($row['count'] - $tags_min + 1)+1)*1.5*100)/100;
				$link = $this->tag_link($row['tag']);
				$html .= "&nbsp;<a style='font-size: ${size}em' href='$link'>$h_tag</a>&nbsp;\n";
			}
			$result->MoveNext();
		}
		return $html;
	}

	private function build_tag_alphabetic() {
		global $database;
		global $config;

		$tags_min = $config->get_int('tags_min');
		$result = $database->Execute(
				"SELECT tag,count FROM tags WHERE count > ? ORDER BY tag",
				array($tags_min));

		$html = "";
		$lastLetter = 0;
		while(!$result->EOF) {
			$row = $result->fields;
			$h_tag = html_escape($row['tag']);
			$count = $row['count'];
			if($lastLetter != strtolower(substr($h_tag, 0, 1))) {
				$lastLetter = strtolower(substr($h_tag, 0, 1));
				$html .= "<p>$lastLetter<br>";
			}
			$link = $this->tag_link($row['tag']);
			$html .= "<a href='$link'>$h_tag&nbsp;($count)</a>\n";
			$result->MoveNext();
		}

		return $html;
	}

	private function build_tag_popularity() {
		global $database;
		global $config;

		$tags_min = $config->get_int('tags_min');
		$result = $database->Execute(
				"SELECT tag,count FROM tags WHERE count > ? ORDER BY count DESC, tag ASC",
				array($tags_min)
				);

		$html = "Results grouped by log<sub>e</sub>(n)";
		$lastLog = 0;
		while(!$result->EOF) {
			$row = $result->fields;
			$h_tag = html_escape($row['tag']);
			$count = $row['count'];
			if($lastLog != floor(log($count))) {
				$lastLog = floor(log($count));
				$html .= "<p>$lastLog<br>";
			}
			$link = $this->tag_link($row['tag']);
			$html .= "<a href='$link'>$h_tag&nbsp;($count)</a>\n";
			$result->MoveNext();
		}

		return $html;
	}
// }}}
// blocks {{{
	private function add_related_block($page, $image) {
		global $database;
		global $config;

		$query = "
			SELECT COUNT(it3.image_id) as count, t3.tag 
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

		$tags = $database->db->GetAll($query, $args);
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

		$tags = $database->db->GetAll($query, $args);
		if(count($tags) > 0) {
			$this->theme->display_popular_block($page, $tags);
		}
	}

	private function add_refine_block($page, $search) {
		global $database;
		global $config;

		$tags = tag_explode($search);
		$s_tags = array_map("sql_escape", $tags);
		$s_tag_list = join(',', $s_tags);

		$query = "
			SELECT t2.tag, COUNT(it2.image_id) AS count
			FROM
				image_tags AS it1,
				image_tags AS it2,
				tags AS t1,
				tags AS t2
			WHERE 
				t1.tag IN($s_tag_list)
				AND it1.image_id=it2.image_id
				AND it1.tag_id = t1.id
				AND it2.tag_id = t2.id
			GROUP BY t2.tag 
			ORDER BY count
			DESC LIMIT ?
		";
		$args = array($config->get_int('tag_list_length'));

		$tags = $database->db->GetAll($query, $args);
		print $database->db->ErrorMsg();
		if(count($tags) > 0) {
			$this->theme->display_refine_block($page, $tags, $search);
		}
	}
// }}}
}
add_event_listener(new TagList());
?>
