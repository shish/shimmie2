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
			$event->add_part($this->build_tag_map($event->image),40);
		}
	}
		
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_bool("tageditcloud_disable",false);
		$config->set_default_bool("tageditcloud_usedfirst",true);
		$config->set_default_string("tageditcloud_sort",'a');
		$config->set_default_int("tageditcloud_minusage",2);
		$config->set_default_int("tageditcloud_defcount",40);
		$config->set_default_int("tageditcloud_maxcount",4096);
		$config->set_default_string("tageditcloud_ignoretags",'tagme');
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

	private function build_tag_map($image) {
		global $database, $config;

		$html = "";
		$cloud = "";
		$precloud = "";

		$sort_method = $config->get_string("tageditcloud_sort");
		$tags_min = $config->get_int("tageditcloud_minusage");
		$used_first = $config->get_bool("tageditcloud_usedfirst");
		$max_count = $config->get_int("tageditcloud_maxcount");
		$def_count = $config->get_int("tageditcloud_defcount");

		$ignore_tags = explode(' ',$config->get_string("tageditcloud_ignoretags"));

		if(class_exists("TagCategories")){
			$categories = $database->get_all("SELECT category, color FROM image_tag_categories");
			$cat_color = array();
			foreach($categories as $row){
				$cat_color[$row['category']] = $row['color'];
			}
		}

		switch($sort_method){
		case 'a':
		case 'p':
			$tag_data = $database->get_all("SELECT tag, FLOOR(LN(LN(count - :tag_min1 + 1)+1)*150)/200 AS scaled, count
				FROM tags WHERE count >= :tag_min2 ORDER BY ".($sort_method == 'a' ? "tag" : "count DESC")." LIMIT :limit",
				array("tag_min1" => $tags_min, "tag_min2" => $tags_min, "limit" => $max_count));
			break;
		case 'r':
			$relevant_tags = "'".implode("','",array_diff($image->tag_array,$ignore_tags))."'";
			$tag_data = $database->get_all("SELECT t2.tag AS tag, COUNT(image_id) AS count, FLOOR(LN(LN(COUNT(image_id) - :tag_min1 + 1)+1)*150)/200 AS scaled
				FROM image_tags it1 JOIN image_tags it2 USING(image_id) JOIN tags t1 ON it1.tag_id = t1.id JOIN tags t2 ON it2.tag_id = t2.id
				WHERE t1.count >= :tag_min2 AND t1.tag IN($relevant_tags) GROUP BY t2.tag ORDER BY count DESC LIMIT :limit",
				array("tag_min1" => $tags_min, "tag_min2" => $tags_min, "limit" => $max_count));
			break;
		}
		
		$counter = 1;
		foreach($tag_data as $row) {
			if($sort_method != 'a' && $counter == $def_count) {
				$cloud .= "<div id='tagcloud_extra' style='display: none;'>\n";
			}

			if(class_exists("TagCategories")){
				$full_tag = $row['tag'];
				$tc = explode(':',$full_tag);
				if(isset($tc[1]) && isset($cat_color[$tc[0]])){
					$h_tag = html_escape($tc[1]);
					$color = '; color:'.$cat_color[$tc[0]];
				} else {
					$h_tag = html_escape($full_tag);
					$color = '';
				}
			} else {
				$full_tag = $row['tag'];
				$h_tag = html_escape($full_tag);
				$color = '';
			}

			$size = sprintf("%.2f", max($row['scaled'],0.5));
			
			if(array_search($row['tag'],$image->tag_array) !== FALSE) {
				if($used_first) {
					$precloud .= "&nbsp;<span onclick='tageditcloud_toggle_tag(this,\"$full_tag\")' class='tag-selected' style='font-size: ${size}em$color' title='${row['count']}'>$h_tag</span>&nbsp;\n";
				} else {
					$counter++;
					$cloud .= "&nbsp;<span onclick='tageditcloud_toggle_tag(this,\"$full_tag\")' class='tag-selected' style='font-size: ${size}em$color' title='${row['count']}'>$h_tag</span>&nbsp;\n";
				}
			} else {
				$counter++;
				$cloud .= "&nbsp;<span onclick='tageditcloud_toggle_tag(this,\"$full_tag\")' style='font-size: ${size}em$color' title='${row['count']}'>$h_tag</span>&nbsp;\n";
			}
		}

		if($precloud != '') {
			$html .= "<div id='tagcloud_set'>$precloud</div>";
		}

		$html .= "<div id='tagcloud_unset'>$cloud</div>";

		if($sort_method != 'a' && $counter > $def_count) {
			$rem = $counter - $def_count;
			$html .= "</div><br>[<span onclick='tageditcloud_toggle_extra(this);' style='color: #0000EF; font-weight:bold;'>show $rem more tags</span>]";
		}

		return "<div id='tageditcloud' class='tageditcloud'>$html</div>"; // FIXME: stupidasallhell
	}

	private function can_tag($image) {
		global $user;
		return ($user->can("edit_image_tag") && (!$image->is_locked() || $user->can("edit_image_lock")));
	}
}
?>
