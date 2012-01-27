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
 *	colorize used tags in cloud || always show used tags in front of cloud
 *	theme junk
 */
class TagEditCloud implements Extension {
        var $theme;

	public function get_priority() {return 50;}

        public function receive_event(Event $event) {
                global $config, $database, $page, $user;
                //if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof ImageInfoBoxBuildingEvent) {
			if(!$config->get_bool("tageditcloud_disable")) {
				if($this->can_tag($event->image)) {
					if(!$cfg_minusage=$config->get_int("tageditcloud_minusage")) $cfg_minusage=2;
					if(!$cfg_defcount=$config->get_int("tageditcloud_defcount")) $cfg_defcount=40;
					if(!$cfg_maxcount=$config->get_int("tageditcloud_maxcount")) $cfg_maxcount=4096;
					if($config->get_string("tageditcloud_sort") != "p") {
						$event->add_part($this->build_tag_map($event->image,$cfg_minusage,false),40);
					} else {
						$event->add_part($this->build_tag_map($event->image,$cfg_defcount,$cfg_maxcount),40);
					}
				}
			}
		}
		
		if($event instanceof InitExtEvent) {
			$config->set_default_bool("tageditcloud_disable",false);
			$config->set_default_bool("tageditcloud_usedfirst",true);
			$config->set_default_string("tageditcloud_sort",'a');
			$config->set_default_int("tageditcloud_minusage",2);
			$config->set_default_int("tageditcloud_defcount",40);
			$config->set_default_int("tageditcloud_maxcount",4096);
		}

		if($event instanceof SetupBuildingEvent) {
			$sort_by = array('Alphabetical'=>'a','Popularity'=>'p');

			$sb = new SetupBlock("Tag Edit Cloud");
			$sb->add_bool_option("tageditcloud_disable", "Disable Tag Selection Cloud: ");
			$sb->add_choice_option("tageditcloud_sort", $sort_by, "<br>Sort the tags by:");
			$sb->add_bool_option("tageditcloud_usedfirst","<br>Always show used tags first: ");
			$sb->add_label("<br><b>Alpha sort</b>:<br>Only show tags used at least ");
			$sb->add_int_option("tageditcloud_minusage");
			$sb->add_label(" times.<br><b>Popularity sort</b>:<br>Show ");
			$sb->add_int_option("tageditcloud_defcount");
			$sb->add_label(" tags by default.<br>Show a maximum of ");
			$sb->add_int_option("tageditcloud_maxcount");
			$sb->add_label(" tags.");

			$event->panel->add_block($sb);
		}
	}

        private function tag_link($tag) {
                $u_tag = url_escape($tag);
                return make_link("post/list/$u_tag/1");
        }
/////	build_tag_map: output cloud of clickable tags
//	build_tag_map($image|false, $defcount, $maxcount|false)	-- taglist sorted by usage, displaying $defcount by default, up to $maxcount via toggle.
//	build_tag_map($image|false, $minusage|false)		-- taglist sorted by alpha, only showing tags with usage >= $minusage 

	private function build_tag_map($image,$defcount,$maxcount) { // 
		global $database,$config;
		$html="";$cloud="";$precloud="";
		$itags=Array();
		$tags_min=1;
		$alphasort=false;
		$usedfirst=$config->get_bool("tageditcloud_usedfirst");

		if(!is_int($defcount)) $defcount=20;
		if(!is_int($maxcount)) {	// Derp this is pretty cheesy.
			$maxcount=4096;		// Hurrrr
			$tags_min=$defcount;
			$alphasort=true;
		}

		if ((gettype($image) == 'object') && (isset($image->tag_array)) && ($itags=$image->tag_array)) $itags=array_fill_keys(array_values($itags),true);

		$tag_data = $database->get_all(" SELECT tag, FLOOR(LOG(2.7, LOG(2.7, count - ? + 1)+1)*1.5*100)/100 AS scaled, count
				FROM tags WHERE count >= ? ORDER BY ".
				(!$alphasort ? "count DESC":"tag").
				" limit $maxcount",
			array($tags_min,$tags_min)
		);
		
		$counter=1;
		foreach($tag_data as $row) {
			if((!$alphasort)&&($counter==$defcount)) $cloud .= "<div id=\"tagcloud_extra\" style=\"display: none;\">";
			$h_tag = html_escape($row['tag']);
			$size = sprintf("%.2f", (float)$row['scaled']/2);
			$usecount=$row['count'];
			$link = $this->tag_link($row['tag']);
			if($size<0.5) $size = 0.5;
			
			if(isset($itags[$row['tag']])) {
				if($usedfirst) {
					$precloud .= "&nbsp;<span onclick=\"tageditcloud_toggle_tag(this)\" class=\"tag-selected\" style='font-size: ${size}em' title='$usecount'>$h_tag</span>&nbsp;\n";
				} else {
					$counter++;
					$cloud .= "&nbsp;<span onclick=\"tageditcloud_toggle_tag(this)\" class=\"tag-selected\" style='font-size: ${size}em' title='$usecount'>$h_tag</span>&nbsp;\n";
				}
			} else {
				$counter++;
				$cloud .= "&nbsp;<span onclick=\"tageditcloud_toggle_tag(this)\" style='font-size: ${size}em' title='$usecount'>$h_tag</span>&nbsp;\n";
			}
		}
		if ($precloud != '') $html .= "<div id=\"tagcloud_set\">$precloud</div>";
		$html .="<div id=\"tagcloud_unset\">$cloud</div>";
		$rem=count($tag_data)-$defcount;
		if((!$alphasort)&&($counter>=$defcount)) $html .= "</div><br>[<span onclick=\"tageditcloud_toggle_extra('tagcloud_extra',this);\" style=\"color: #0000EF; font-weight:bold;\">show $rem more tags</span>]";
//		$html.='<pre>'.var_export($itags,true).'</pre>';
		return "<div id=\"tageditcloud\" class=\"tageditcloud\">$html</div>"; // FIXME: stupidasallhell
	}


	private function can_tag($image) {
		global $config, $user;
		return (
			($config->get_bool("tag_edit_anon") || !$user->is_anonymous()) &&
			($user->is_admin() || !$image->is_locked())
			);
	}

}
?>
