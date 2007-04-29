<?php

class TagList extends Extension {
// event handling {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "tags")) {
			global $page;
			$page->set_title("Tag List");
			$page->add_side_block(new Block("Navigation", $this->build_navigation()), 0);

			switch($event->get_arg(0)) {
				default:
				case 'map':
					$page->set_heading("Tag Map");
					$page->add_main_block(new Block("Tags", $this->build_tag_map()));
					break;
				case 'alphabetic':
					$page->set_heading("Alphabetic Tag List");
					$page->add_main_block(new Block("Tags", $this->build_tag_alphabetic()));
					break;
				case 'popularity':
					$page->set_heading("Tag List by Popularity");
					$page->add_main_block(new Block("Tags", $this->build_tag_popularity()));
					break;
			}
		}
		if(is_a($event, 'PageRequestEvent') && ($event->page == "index")) {
			global $config;
			global $page;
			if($config->get_int('popular_count') > 0) {
				if(isset($_GET['search'])) {
					$page->add_side_block(new Block("Refine Search", $this->get_refiner_tags($_GET['search'])), 60);
				}
				else {
					$page->add_side_block(new Block("Popular Tags", $this->get_popular_tags()), 60);
				}
			}
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			global $page;
			$page->add_side_block(new Block("Related Tags", $this->get_related_tags($event->get_image())), 60);
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Tag Map Options");
			$sb->add_label("Ignore tags used fewer than ");
			$sb->add_int_option("tags_min", 0, 99);
			$sb->add_label(" times");
			$event->panel->add_main_block($sb);

			$sb = new SetupBlock("Popular Tag List");
			$sb->add_label("Show top ");
			$sb->add_int_option("popular_count", 0, 60);
			$sb->add_label(" tags");
			$sb->add_label("<br>Tag info link: ");
			$sb->add_text_option("info_link", true);
			$event->panel->add_main_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_int_from_post("tags_min");

			$event->config->set_int_from_post("popular_count");
			$event->config->set_string_from_post("info_link");
		}
	}
// }}}
// misc {{{
	private function tag_link($tag) {
		$h_tag = html_escape($tag);
		return make_link("index", "search=$h_tag");
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
		$result = $database->db->Execute(
				"SELECT tag,COUNT(image_id) AS count FROM tags GROUP BY tag HAVING count > ? ORDER BY tag",
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
		$result = $database->db->Execute(
				"SELECT tag,COUNT(image_id) AS count FROM tags GROUP BY tag HAVING count > ? ORDER BY tag",
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
		$result = $database->db->Execute(
				"SELECT tag,COUNT(image_id) AS count FROM tags GROUP BY tag HAVING count > ? ORDER BY count DESC, tag ASC",
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
// get related {{{
	private function get_related_tags($image) {
		global $database;
		global $config;

		$query = "
			SELECT COUNT(t3.image_id) as count, t3.tag 
			FROM
				tags AS t1,
				tags AS t2,
				tags AS t3 
			WHERE
				t1.image_id=?
				AND t1.tag=t2.tag
				AND t2.image_id=t3.image_id
				AND t1.tag != 'tagme'
				AND t3.tag != 'tagme'
			GROUP by t3.tag
			ORDER by count DESC
			LIMIT ?
		";

		$n = 0;
		$html = "";
		$result = $database->db->Execute($query, array($image->id, $config->get_int('popular_count')));
		while(!$result->EOF) {
			$row = $result->fields;
			$h_tag = html_escape($row['tag']);
			$count = $row['count'];
			if($n++) $html .= "<br/>";
			$link = $this->tag_link($row['tag']);
			$html .= "<a href='$link'>$h_tag</a>\n";
			$result->MoveNext();
		}
		$result->Close();

		return $html;
	}
// }}}
// get popular {{{
	private function get_popular_tags() {
		global $database;
		global $config;

		$query = "
			SELECT tag, COUNT(image_id) AS count
			FROM tags
			GROUP BY tag
			ORDER BY count DESC
			LIMIT ?
		";

		$n = 0;
		$result = $database->db->Execute($query, array($config->get_int('popular_count')));
		$html = "";
		while(!$result->EOF) {
			$row = $result->fields;
			$tag = html_escape($row['tag']);
			$count = $row['count'];
			if($n++) $html .= "<br/>";
			$link = $this->tag_link($row['tag']);
			$html .= "<a href='$link'>$tag ($count)</a>\n";
			if(!is_null($config->get_string('info_link'))) {
				$link = str_replace('$tag', $tag, $config->get_string('info_link'));
				$html .= "<a href='$link'>?</a>\n";
			}
			$result->MoveNext();
		}
		$result->Close();

		$html .= "<p><a href='".make_link("tags")."'>Full List &gt;&gt;&gt;</a>\n";

		return $html;
	}
// }}}
// get refine {{{
	private function get_refiner_tags($search) {
		global $database;
		global $config;

		$tags = tag_explode($search);
		$s_tags = array_map("sql_escape", $tags);
		$s_tag_list = join(',', $s_tags);

		$query = "
			SELECT t2.tag, COUNT(t2.image_id) AS count
			FROM
				tags AS t1,
				tags AS t2
			WHERE 
				t1.tag IN($s_tag_list)
				AND t1.image_id=t2.image_id
			GROUP BY t2.tag 
			ORDER BY count
			DESC LIMIT ?
		";

		$n = 0;
		$html = "";
		$result = $database->db->Execute($query, array($config->get_int('popular_count')));
		while(!$result->EOF) {
			$row = $result->fields;
			$h_tag = html_escape($row['tag']);
			$count = $row['count'];
			if($n++) $html .= "<br/>";
			$link = $this->tag_link($row['tag']);
			$html .= "<a href='$link'>$h_tag</a>\n";
			$result->MoveNext();
		}
		$result->Close();

		return $html;
	}
// }}}
}
add_event_listener(new TagList());
?>
