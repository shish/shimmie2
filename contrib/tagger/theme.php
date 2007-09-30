<?php
// Tagger - Advanced Tagging
// Author: Artanis (Erik Youngren <artanis.00@gmail.com>)
// Do not remove this notice.

class taggerTheme extends Themelet {

	public function build($event,$tags) {
		// When overriding this function, take care that all tag attributes are
		// maintained UNCHANGED. There are no attributes in the HTML code below
		// that go unused by the javascript. If you fail to do this,
		// the extension will BREAK!
		//
		// Now that that's sunk in, chaniging title attributes is fine.
		global $config;
		global $page;
		
		// set up data
		$base_href = $config->get_string('base_href');
		$tagme     = $config->get_string(
			'ext-tagger_clear-tagme','N')=="Y"?
			"<input type='hidden' id='tagme' tag='tagme'></input>":
			null;
		$url_about = make_link("about/tagger");
		
		// build floater tags
		$h_tags = "";
		foreach($tags as $tag) {
			$h_tags .= $this->tag_to_html($tag);
		}
		
		$html = "
		<img src='$base_href/ext/tagger/onload.gif'	style='display:none;'
			onload='taggerInit();' />
		<span style='font-size:0.7em;'>
			Collapse this block to hide Tagger
		</span>
		<br/>
		<a onclick='taggerResetPos();' class='tagger_js'>Default Location</a>
		<hr/>
		<a href='$url_about'>About Tagger</a>".
		// Tagger Floater
		"<div id='tagger_window'>
			<div id='tagger_titlebar' title='Drag to move'>Tagger</div>			
			<div id='tagger_filter'>
				<input type='text' id='tagger_new-tag' value='' size='12'
					onfocus='tagger_filter_focus = true;'
					onblur='tagger_filter_focus = false;'
					onkeyup='tagger_filter();' focus='' title='Type to search' >
				</input>
				<input type='button' value='Add' tag='' title='Add typed tag'
					onclick='
						this.attributes.tag.value=
							byId(\"tagger_new-tag\").value;
						toggleTag(this);'>
				</input>
				<input type='button' value='Set' onclick='pressSet();'
					title='Save tags'></input>
				$tagme
				<hr/>
				<a id='tagger_mode' class='tagger_js' mode='all'
					onclick='taggerToggleMode()'>View Applied Tags</a> |
				<a onclick='tagger_tagIndicators(); tagger_filter(true);'
					class='tagger_js' >Refresh Filter</a>
			</div>
			<div id='tagger_body'>$h_tags</div>
		</div>";
		
		$page->add_block( new Block(
			"Tagger",
			$html,
			"left"));
		$page->add_header(
			"<script
				src='$base_href/ext/tagger/webtoolkit.drag.js'
				type='text/javascript'></script>");
	}
	

	final public function show_about ($event) {
		// The about page for Tagger. No override. Feel free to CSS it, though.
		global $page;
		global $config;
		$base_href = $config->get_string('base_href');
		
		$script1 = "$base_href/ext/tagger/script.js";
		$script2 = "$base_href/ext/tagger/webtoolkit.drag.js";
		
		$html = str_replace("\"","&quot;",str_replace("\'","&#39;","
		<ul id='Tagger'>
			<li>
				If Tagger is in your way, click and drag it\'s title bar to move
				it to a more convienient location.
			<li>
				Click the links to add the tag to the image\'s tag list, when
				done, press	the Set button to save the tags.
			</li>
			<li>
				<p>Tagger gets all the tags with 2 or more uses, so the list can
				get quite large. If you are having trouble finding the tag you
				are looking for, you can enter it into the box at the top and as
				you type, Tagger will remove tags that do not match to aid your
				search. Usually, you\'ll only need one or two letters to trim
				the list down to the tag you are looking for.
				</p>
				<p>One letter filters will look only at the first letter of the
				tag. Two or more letters will search the beginning of every
				word in every tag.
				</p>
				<p>If the tag is not in the list, finish typing out the tag and
				click \"Add\" to add the tag to the image\'s tag list.
				</p>
				<p>Tags must have two uses to appear in Tagger\'s list, so
				you'll have to enter the tag for at least one other image for it
				to show up.
				</p>
			</li>
			<li><h4>Requirements</h4>
				<p>Tagger requires javascript for its functionality. Sorry, but
				there\'s no other way to accomplish the tag list
				modifications.
				</p>
				<p>If you have javascript completely disabled, you will not be
				able to use Tagger.
				</p>
				<p>Depending on your method of disabling javascript, you may be
				able to whitelist scripts. The script files used by Tagger are
				<a href='$script1'>script.js</a> and
				<a href='$script2'>webtoolkit.drag.js</a>.
				</p>
			</li>
		</ul>"));
		
		$page->set_title("Shimmie - About / Tagger - Advanced Tagging");
		$page->set_heading("About / Tagger - Advanced Tagging");
		$page->add_block( new Block("Author",
			"Artanis (Erik Youngren &lt;artanis.00@gmail.com&gt;)","main",0));
		$page->add_block( new Block("Use", $html,"main",1));
	}


	final function tag_to_html ($tag) {
		// Important for script.js, no override. You can CSS this, though.
		// If you must, remove the 'final' keyword, but MAKE SURE the entire <A>
		// tag is COPIED TO THE NEW FUNCTION EXACTLY! If you fail to do this,
		// the extension will BREAK!
		$tag_name = $tag['tag'];
		$tag_id = $this->tag_id($tag_name);
		$stag = $this->trimTag($tag_name,20,"_");
		
		$html = "
			<a id='$tag_id' title='Apply &quot;$tag_name&quot;' tag='$tag_name'
				onclick='toggleTag(this)'>$stag</a>";
		
		return $html;
	}
	
	// Important for script.js, no override.
	final function tag_id ($tag) {
		$tag_id = "";
		$m=null;
		for($i=0; $i < strlen($tag); $i++) {
			$l = substr($tag,$i,1);
			$m=null;
			preg_match("[\pP]",$l,$m);
			$tag_id .= !isset($m[0]) ? $l:"_";
		}
		
		return trim(str_replace("__","_",$tag_id)," _");
	}
	
	function trimTag($s,$len=80,$br=" ") {
		if(strlen($s) > $len) {
			$s = substr($s, 0,$len-1);
			$s = substr($s,0, strrpos($s,$br))."...";
		}
		return $s;
	}
}

?>
