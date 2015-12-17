<?php
/*
 * Name: Tag EditCloud
 * Author: AtomicDryad
 * Contributors:
 *   Schizius (Relevance Sort, Category Integration, Cleanup)
 * Description: Add or remove tags to the editor via clicking.
 */

/* Todo:
 * 	usepref(todo2: port userpref)
 *	theme junk
 */
class TagEditCloud extends Extension {
	public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event) {
		global $config;

		if(!$config->get_bool("tageditcloud_disable") && $this->can_tag($event->image)) {
			$html = $this->build_tag_map($event->image);
			if(!is_null($html)) {
				$event->add_part($html, 40);
			}
		}
	}

	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_bool("tageditcloud_disable", false);
		$config->set_default_bool("tageditcloud_usedfirst", true);
		$config->set_default_string("tageditcloud_sort", 'a');
		$config->set_default_int("tageditcloud_minusage", 2);
		$config->set_default_int("tageditcloud_defcount", 40);
		$config->set_default_int("tageditcloud_maxcount", 4096);
		$config->set_default_string("tageditcloud_ignoretags", 'tagme');
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sort_by = array('Alphabetical'=>'a','Popularity'=>'p','Relevance'=>'r');

		$sb = new SetupBlock("Tag Edit Cloud");
		$sb->add_bool_option("tageditcloud_disable", "Disable Tag Selection Cloud: ");
		$sb->add_choice_option("tageditcloud_sort", $sort_by, "<br>Sort the tags by:");
		$sb->add_bool_option("tageditcloud_usedfirst","<br>Always show used tags first: ");
		$sb->add_label("<br><b>Alpha sort</b>:<br>Only show tags used at least ");
		$sb->add_int_option("tageditcloud_minusage");
		$sb->add_label(" times.<br><b>Popularity/Relevance sort</b>:<br>Show ");
		$sb->add_int_option("tageditcloud_defcount");
		$sb->add_label(" tags by default.<br>Show a maximum of ");
		$sb->add_int_option("tageditcloud_maxcount");
		$sb->add_label(" tags.");
		$sb->add_label("<br><b>Relevance sort</b>:<br>Ignore tags (space separated): ");
		$sb->add_text_option("tageditcloud_ignoretags");

		$event->panel->add_block($sb);
	}

	/**
	 * @param Image $image
	 * @return string
	 */
	private function build_tag_map(Image $image) {
		global $database, $config;

		$html = "";
		$cloud = "";
		$precloud = "";
		$postcloud = "";

		$sort_method = $config->get_string("tageditcloud_sort");
		$tags_min = $config->get_int("tageditcloud_minusage");
		$used_first = $config->get_bool("tageditcloud_usedfirst");
		$max_count = $config->get_int("tageditcloud_maxcount");
		$def_count = $config->get_int("tageditcloud_defcount");

		$ignore_tags = Tag::explode($config->get_string("tageditcloud_ignoretags"));

		if(ext_is_live("TagCategories")) {
			$categories = $database->get_all("SELECT category, color FROM image_tag_categories");
			$cat_color = array();
			foreach($categories as $row) {
				$cat_color[$row['category']] = $row['color'];
			}
		}

		switch($sort_method) {
			case 'a':
			case 'p':
			default:
				$order_by = $sort_method == 'a' ? "tag" : "count DESC";
				$tag_data = $database->get_all("
					SELECT tag, FLOOR(LN(LN(count - :tag_min1 + 1)+1)*150)/200 AS scaled, count
					FROM tags
					WHERE count >= :tag_min2
					ORDER BY $order_by
					LIMIT :limit",
					array("tag_min1" => $tags_min, "tag_min2" => $tags_min, "limit" => $max_count));
				break;
			case 'r':
				$relevant_tags = array_diff($image->get_tag_array(),$ignore_tags);
				if(count($relevant_tags) == 0) {
					return null;
				}
				$relevant_tags = implode(",",array_map(array($database,"escape"),$relevant_tags));
				$tag_data = $database->get_all("
					SELECT t2.tag AS tag, COUNT(image_id) AS count, FLOOR(LN(LN(COUNT(image_id) - :tag_min1 + 1)+1)*150)/200 AS scaled
					FROM image_tags it1
					JOIN image_tags it2 USING(image_id)
					JOIN tags t1 ON it1.tag_id = t1.id
					JOIN tags t2 ON it2.tag_id = t2.id
					WHERE t1.count >= :tag_min2 AND t1.tag IN($relevant_tags)
					GROUP BY t2.tag
					ORDER BY count DESC
					LIMIT :limit",
					array("tag_min1" => $tags_min, "tag_min2" => $tags_min, "limit" => $max_count));
				break;
		}

		$counter = 1;
		foreach($tag_data as $row) {
			$full_tag = $row['tag'];

			if(ext_is_live("TagCategories")){
				$tc = explode(':',$row['tag']);
				if(isset($tc[1]) && isset($cat_color[$tc[0]])){
					$h_tag = html_escape($tc[1]);
					$color = '; color:'.$cat_color[$tc[0]];
				} else {
					$h_tag = html_escape($row['tag']);
					$color = '';
				}
			} else {
				$h_tag = html_escape($row['tag']);
				$color = '';
			}

			$size = sprintf("%.2f", max($row['scaled'],0.5));
			$js = htmlspecialchars('tageditcloud_toggle_tag(this,'.json_encode($full_tag).')',ENT_QUOTES); //Ugly, but it works

			if(array_search($row['tag'],$image->get_tag_array()) !== FALSE) {
				if($used_first) {
					$precloud .= "&nbsp;<span onclick='{$js}' class='tag-selected' style='font-size: ${size}em$color' title='${row['count']}'>{$h_tag}</span>&nbsp;\n";
					continue;
				} else {
					$entry = "&nbsp;<span onclick='{$js}' class='tag-selected' style='font-size: ${size}em$color' title='${row['count']}'>{$h_tag}</span>&nbsp;\n";
				}
			} else {
				$entry = "&nbsp;<span onclick='{$js}' style='font-size: ${size}em$color' title='${row['count']}'>{$h_tag}</span>&nbsp;\n";
			}

			if($counter++ <= $def_count) {
				$cloud .= $entry;
			} else {
				$postcloud .= $entry;
			}
		}

		if($precloud != '') {
			$html .= "<div id='tagcloud_set'>{$precloud}</div>";
		}

		if($postcloud != '') {
			$postcloud = "<div id='tagcloud_extra' style='display: none;'>{$postcloud}</div>";
		}

		$html .= "<div id='tagcloud_unset'>{$cloud}{$postcloud}</div>";

		if($sort_method != 'a' && $counter > $def_count) {
			$rem = $counter - $def_count;
			$html .= "</div><br>[<span onclick='tageditcloud_toggle_extra(this);' style='color: #0000EF; font-weight:bold;'>show {$rem} more tags</span>]";
		}

		return "<div id='tageditcloud' class='tageditcloud'>{$html}</div>"; // FIXME: stupidasallhell
	}

	/**
	 * @param Image $image
	 * @return bool
	 */
	private function can_tag(Image $image) {
		global $user;
		return ($user->can("edit_image_tag") && (!$image->is_locked() || $user->can("edit_image_lock")));
	}
}

