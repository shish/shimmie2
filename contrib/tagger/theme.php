<?php
class taggerTheme extends Themelet {
	public function build ($page, $tags) {	
	$html = "<div id='tagger_window' style='top:100px;left:800px;'>";
	$html .= "<div id='tagger_titlebar' title='Drag to move' onmousedown='dragStart(event,\"tagger_window\");'>";
	$html .= "Tagger";
	$html .= "</div>";
	$html .= "<div id='tagger_body' style='height:300px;'>";
	$html .= "<input type='text' id='custTag' value=''></input><input type='button' value='Add' onclick='addTagById(\"custTag\")'></input><br/>";
	foreach ($tags as $tag) {
		$tag_name = $this->trimTag($tag['tag'],32);
		$html .= "<a style='cursor:pointer;' onclick='addTag(\"".$tag['tag']."\");'title='Add \"".$tag['tag']."\" to the tag list'>".$tag_name."</a><br/>";
	}
	$html .= "</div></div>";
	
	$page->add_block( new Block("Tagger",
		"<span style='font-size:.8em;'>Collapse this block to hide Tagger.</span><br/><br/>Use: Click the links to add the tag to the list, when done, press Set to save the tags.".$html,
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
}
?>
