<?php
// Tagger - Advanced Tagging
// Author: Artanis (Erik Youngren <artanis.00@gmail.com>)
// Do not remove this notice.

class taggerTheme extends Themelet {
	public function build ($page, $tags) {	
		global $config;
		
		$tagme = $config->get_string(
			"ext-tagger_clear-tagme","N") == "Y" ?"true":"false";
		$base_href = $config->get_string("base_href");
		
		$tag_html = "";
		foreach ($tags as $tag) {
			$tag_name = $tag['tag'];
			$tag_trunc = $this->trimTag($tag_name,20,"_");
			$tag_html .= "
				<div id='tagger_tag_".$tag_name."'>
					<a style='cursor:pointer;'
						 onclick='toggleTag(&quot;".$tag_name."&quot;,".$tagme.");'
						 title='Add &quot;".$tag_name."&quot; to the tag list'>".$tag_trunc."
					</a>
				</div>";
		}
		$url_more = make_link("about/tagger");
		
		$html = <<<EOD
<img style='display:none;' src='$base_href/ext/tagger/onload.gif'
				onload='setTagIndicators();'/>
<span style="font-size:.7em;">Collapse this block to hide Tagger.</span>
<br/>
<a onclick="taggerResetPos();" style="cursor:pointer;">Default Location</a>
<hr/>
<a href='$url_more'>About Tagger</a>
<div id="tagger_window" style="bottom:25px;right:25px;">
	<div id="tagger_titlebar" title="Drag to move"
					onmousedown="dragStart(event,&quot;tagger_window&quot;);">
		Tagger
	</div>
	<div id="tagger_filter">

		<input type="text" id="tagger_custTag" value="" onfocus="_f_custTag = true;"
						onblur="_f_custTag = false;"
						onkeyup="tagger_filter(&quot;tagger_custTag&quot;)" size='12'>
		</input>
		<input type="button" value="Add"
					onclick="addTagById(&quot;tagger_custTag&quot;)">
		</input>		<input type="button" onclick="pushSet(&quot;imgdata&quot;);"
						value="Set">
		</input>
		<hr/>
	</div>
	<div id="tagger_body" style="height:200px;">$tag_html</div>
</div>
EOD;
		$page->add_block( new Block("Tagger - Advanced Tagging",
			"".$html,
			"left",
			50));
	}
	
	public function trimTag($s,$len=80,$break=" ") {
		if(strlen($s) > $len) {
			$s = substr($s, 0,$len-1);
			$s = substr($s,0, strrpos($s,$break))."...";
		}
		return $s;
	}
	
	public function show_about ($event) {
		global $page;
		$html = <<<EOD
<ul id="Tagger">
	<li>
		If Tagger is in you way, click and drag it&#39;s title bar to move it to a
		more convienient location.
	<li>
		Click the links to add the tag to the image&#39;s tag list, when done, press
		the Set button to save the tags.
	</li>
	<li>
		<p>Tagger gets all the tags in use with 2 or more uses, so the list can get
		quite large. If you are having trouble finding the tag you are looking for,
		you can enter it into	the box at the top and as you type, Tagger will remove
		tags that do not match to aid your search. Usually, you&#39;ll only need one
		or two letters to trim the list down to	the tag you are looking for.</p>
		<p>If the tag is not in the list, finish typing out the tag and click "Add"
		to add the tag to the image&#39;s tag list.</p>
		<p>Tags must have two uses to appear in Tagger&#39;s list, so you'll have to
		enter the tag for at least one other image for it to show up.</p>
	</li>
	<li>
		<p>Tagger requires javascript for its functionality. Sorry, but there&#39;s
		no other way to accomplish the tag list modifications.</p>
		<p>If you have javascript completely disabled, you will not be able to use
		Tagger.</p>
		<p>Due to the manner in which Tagger is constructed, it will hide along with
		it&#39;s block on the side bar and block behaviour will remember that
		setting in shimmie&#39;s cookies.</p>
	</li>
</ul>
EOD;
	$page->set_title("About / Extension / Tagger - Advanced Tagging");
	$page->set_heading("About / Extension / Tagger - Advanced Tagging");
	$page->add_block( new Block("Author",
		"Artanis (Erik Youngren &lt;artanis.00@gmail.com&gt;)","main",0));
	$page->add_block( new Block("Use", $html,"main",1));
	}
}
?>
