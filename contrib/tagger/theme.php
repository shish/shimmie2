<?php
class taggerTheme extends Themelet {
	public function build ($page, $tags) {	
	$tag_html = "";
	foreach ($tags as $tag) {
		$tag_name = $tag['tag'];
		$tag_trunc = $this->trimTag($tag_name,32);
		$tag_html .= "<div id='tagger_tag_".$tag_name."'>"."
			<a style='cursor:pointer;' onclick='addTag(&quot;".$tag_name."&quot;);' ".
			"title='Add &quot;".$tag_name."&quot; to the tag list'>".$tag_trunc."</a>".
			"</div>";
	}
	$url_more = make_link("about/tagger");
	$html = <<<EOD
<span style="font-size:.7em;">Collapse this block to hide Tagger.</span>
<br/>
<h4>Use</h4>
<ul>
	<li>Click the links to add the tag to the list, when done, press Set to save the tags.</li>
	<li>Type in the filter box to remove tags you aren&#39;t looking for.</li>
	<li>Enter tags not on the list in the second box. Tags must have 2 uses to display in the list.</li>
	<li><a href='$url_more'>More</a></li>
</ul>
<div id="tagger_window" style="top:100px;left:800px;">
	<div id="tagger_titlebar" title="'Drag to move" onmousedown="dragStart(event,&quot;tagger_window&quot;);">
	Tagger
	</div>
	<div id="tagger_body" style="height:300px;">
		<input type="text" id="tagger_filter" onkeyup="tagger_filter(&quot;tagger_filter&quot;)" size="12"></input>
		<hr/>
		<input type="text" id="custTag" value="" size='12'></input>
		<input type="button" value="Add" onclick="addTagById(&quot;custTag&quot;)"></input>
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
<h4>Author</h4>
Erik Youngren (Artanis) artanis.00@gmail.com
<h4>Use</h4>
<ul>
	<li>Click the links to add the tag to the image&#39;s tag list, when done, press Set to save the tags.</li>
	<li>Type in the filter box to remove tags you aren&#39;t looking for.<ul>
		<li>Single letter filters will only match the first letter of the tags.</li>
		<li>2 or more will match that letter combination anywhere in the tag. Starting a
		filter with a space (' ') will prevent this behaviour.</li>
	</ul></li>
	<li>Enter tags not on the list in the second box. Tags must have 2 uses to display in the list.</li>
</ul>
EOD;

	$page->add_block( new Block("About Tagger", $html,"main"));
	}
}
?>
