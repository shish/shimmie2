<?php
/*
 * Name: Tag EditCloud
 * Author: AtomicDryad
 * Description: Add or remove tags to the editor via clicking.
 */

/* Todo:
 *	Be less kludgy
 * 	$cfgstub->board prefs
 * 	toggle sorting method via javascript || usepref(todo2: port userpref)
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

        private function tag_link($tag) {
                $u_tag = url_escape($tag);
                return make_link("post/list/$u_tag/1");
        }

	private function build_tag_map($image) { // 
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

			$h_tag = html_escape($row['tag']);
			$size = sprintf("%.2f", max($row['scaled'],0.5));
			
			if(array_search($row['tag'],$image->tag_array) !== FALSE) {
				if($used_first) {
					$precloud .= "&nbsp;<span onclick='tageditcloud_toggle_tag(this)' class='tag-selected' style='font-size: ${size}em' title='${row['count']}'>$h_tag</span>&nbsp;\n";
				} else {
					$counter++;
					$cloud .= "&nbsp;<span onclick='tageditcloud_toggle_tag(this)' class='tag-selected' style='font-size: ${size}em' title='${row['count']}'>$h_tag</span>&nbsp;\n";
				}
			} else {
				$counter++;
				$cloud .= "&nbsp;<span onclick='tageditcloud_toggle_tag(this)' style='font-size: ${size}em' title='${row['count']}'>$h_tag</span>&nbsp;\n";
			}
		}

		if($precloud != '') {
			$html .= "<div id='tagcloud_set'>$precloud</div>";
		}

		$html .= "<div id='tagcloud_unset'>$cloud</div>";

		$rem = count($tag_data) - $def_count;
		if($sort_method != 'a' && $counter >= $defcount) {
			$html .= "</div><br>[<span onclick='tageditcloud_toggle_extra(this);' style='color: #0000EF; font-weight:bold;'>show $rem more tags</span>]";
		}

		return "<div id='tageditcloud' class='tageditcloud'>$html</div>"; // FIXME: stupidasallhell
	}


	private function can_tag($image) {
		global $config, $user;
		return (
			$user->can("edit_image_tag") &&
			(!$image->is_locked() || $user->can("edit_image_lock"))
		);
	}

}
?>
