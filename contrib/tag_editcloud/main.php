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

        public function receive_event(Event $event) {
                global $config, $database, $page, $user;
                //if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof ImageInfoBoxBuildingEvent) {
			if($this->can_tag($event->image)) {
				$cfgstub_sortbyname=false;	// FIXME
				$cfgstub_showtop=40;		// Derp
				$cfgstub_minuse=2;		// Derp
				if($cfgstub_sortbyname) {
					$event->add_part($this->build_tag_map($event->image,$cfgstub_minuse,false),40);
				} else {
					$event->add_part($this->build_tag_map($event->image,$cfgstub_showtop,4096),40);
				}
			}
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

		global $database;
		$html="";$cloud="";$precloud="";
		$itags=Array();
		$tags_min=1;
		$alphasort=false;
		if(!is_int($defcount)) $defcount=20;
		if(!is_int($maxcount)) {	// Derp this is pretty cheesy.
			$maxcount=4096;		// Hurrrr
			$tags_min=$defcount;
			$alphasort=true;
		}

		if ((gettype($image) == 'object') && (isset($image->tag_array)) && ($itags=$image->tag_array)) $itags=array_fill_keys(array_values($itags),true);

		$result = $database->execute(" SELECT tag, FLOOR(LOG(2.7, LOG(2.7, count - ? + 1)+1)*1.5*100)/100 AS scaled, count
				FROM tags WHERE count >= ? ORDER BY ".
				(!$alphasort ? "count DESC":"tag").
				" limit $maxcount",
			array($tags_min,$tags_min)
		);
		


		$tag_data = $result->GetArray();
		$counter=1;
		foreach($tag_data as $row) {
			if((!$alphasort)&&($counter==$defcount)) $cloud .= "<div id=\"tagcloud_extra\" style=\"display: none;\">";
			$h_tag = html_escape($row['tag']);
			$size = sprintf("%.2f", (float)$row['scaled']/2);
			$usecount=$row['count'];
			$link = $this->tag_link($row['tag']);
			if($size<0.5) $size = 0.5;
			if(isset($itags[$row['tag']])) {
//				if($size<0.75) $size = 0.75;
				$precloud .= "&nbsp;<span onclick=\"tageditcloud_toggle_tag(this)\" class=\"tag-selected\" style='font-size: ${size}em' title='$usecount'>$h_tag</span>&nbsp;\n";
			} else {
				$counter++;
				$cloud .= "&nbsp;<span onclick=\"tageditcloud_toggle_tag(this)\" style='font-size: ${size}em' title='$usecount'>$h_tag</span>&nbsp;\n";
			}
		}
		if ($precloud != '') $html .= "<div id=\"tagcloud_set\">$precloud</div>";
		$html .="<div id=\"tagcloud_unset\">$cloud</div>";
		$rem=count($tag_data)-$defcount;
//		$script = "";
//		$html.=$script;
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
add_event_listener(new TagEditCloud());
?>
