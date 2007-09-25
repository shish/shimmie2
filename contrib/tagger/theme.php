<?php 
class taggerTheme extends Themelet { 
	public function build ($page, $tags) { 
		$html = "<div onmousedown='dragStart(event);' style='position:fixed;top:100px;left:800px;height:400px;overflow:scroll;padding:1em;border:2px solid;background-color:white;'>"; 
		foreach ($tags as $tag) { 
			$html .= "<input type='button' style='width:10em;' onclick='javascript:addTag(\"".$tag['tag']."\");' value='".$tag['tag']."'></input><br/>"; 
		} 
		$html .= "</div>"; 

		$page->add_block( new Block(null, 
					$html, 
					"main", 
					1000)); 
	} 
} 
?>
