<?php
class taggerTheme extends Themelet {
	public function build ($page, $tags) {	
	
	$tag_html = "";
	foreach ($tags as $tag) {
		$tag_name = $tag['tag'];
		$tag_trunc = $this->trimTag($tag_name,16);
		$tag_html .= "<div id='tagger_tag_".$tag_name."'>"."
			<a style='cursor:pointer;' onclick='toggleTag(&quot;".$tag_name."&quot;);' ".
			"title='Add &quot;".$tag_name."&quot; to the tag list'>".$tag_trunc."</a>".
			"</div>";
	}
	$url_more = make_link("about/tagger");
	
	$html = <<<EOD
<span style="font-size:.7em;">Collapse this block to hide Tagger.</span>
<br/>
<a href='$url_more'>About Tagger</a>
<div id="tagger_window" style="top:100px;left:100px;" onmousedown="setTagIndicators(&quot;tagger_window&quot;);">
	<div id="tagger_titlebar" title="'Drag to move" onmousedown="dragStart(event,&quot;tagger_window&quot;);">
	Tagger
	</div>
	<div id="tagger_body" style="height:300px;">
		<input type="text" id="tagger_custTag" value="" onkeyup="tagger_filter(&quot;tagger_custTag&quot;)"  size='12'></input>
		<input type="button" value="Add" onclick="addTagById(&quot;tagger_custTag&quot;)"></input>
		<hr/>
			$tag_html
	</div>
</div>
EOD;
	$page->add_block( new Block("Tagger",
		"".$html,
		"left",
		0));
	}
	
	public function trimTag($s,$len=80) {
		if(strlen($s) > $len) {
			$s = substr($s, 0,$len-1);
			$s = substr($s,0, strrpos($s,'_'))."...";
		}
		return $s;
	}
	
	public function show_about ($event) {
		global $page;
		$html = <<<EOD
<ul>
	<li>Click the links to add the tag to the image&#39;s tag list, when done, press Set to save the tags.</li>
	<li>
		If you are having trouble finding the tag you are looking for, enter it into the box at the top.<br/>
		As you type, Tagger will remove tags that do not match to aid your search.<br/>
		If it is not in the list, click Add to add the tag to the image&#39;s tag list.<br/>
		Tags must have two uses to appear in Tagger&#39;s list, so you'll have to enter the tag at least once more.
	</li>
</ul>
EOD;
	$page->set_title("About Extension: Tagger");
	$page->set_heading("About Extension: Tagger");
	$page->add_block( new Block("Author","Erik Youngren (Artanis) artanis.00@gmail.com","main",0));
	$page->add_block( new Block("Use", $html,"main",1));
	}
}
?>
