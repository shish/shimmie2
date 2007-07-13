<?php
class LinkImageTheme extends Themelet {
	public function links_block($page,$data) {
		
		$thumb_src = $data['thumb_src'];
		$image_src = $data['image_src'];
		$post_link = $data['post_link'];
		$text_link	 = $data['text_link'];
		
		$page->add_block( new Block(
			"Link to Image",
			"<fieldset>".
				"<legend><a href='http://en.wikipedia.org/wiki/Bbcode' target='_blank'>BBCode</a></legend>".
					$this->link_code("Text Link",$this->url($post_link, $text_link,"ubb"),"ubb_text-link").
					$this->link_code("Thumbnail Link",$this->url($post_link, $this->img($thumb_src,"ubb"),"ubb"),"ubb_thumb-link").
					$this->link_code("Inline Image", $this->img($image_src,"ubb"), "ubb_full-img").
				"</fieldset>".
				
				"<fieldset>".
				"<legend><a href='http://en.wikipedia.org/wiki/Html' target='_blank'>HTML</a></legend>".
					$this->link_code("Text Link", $this->url($post_link, $text_link,"html"), "html_text-link").
					$this->link_code("Thumbnail Link", $this->url($post_link,$this->img($thumb_src,"html"),"html"), "html_thumb-link").
					$this->link_code("Inline Image", $this->img($image_src,"html"), "html_full-image").
				"</fieldset>".
				
				"<fieldset>".
					"<legend>Plain Text</legend>".
					$this->link_code("Post URL",$post_link,"text_post-link").
					$this->link_code("Thumbnail URL",$thumb_src,"text_thumb-url").
					$this->link_code("Image URL",$image_src,"text_image-src").
				"</fieldset>",
			"main",
			50));
	}
	
	private function url ($url,$content,$type) {
		if ($content == NULL) {$content=$url;}
		
		switch ($type) {
			case "html":
				$text = "<a href=\"".$url."\">".$content."</a>";
				break;
			case "ubb":
				$text = "[url=".$url."]".$content."[/url]";
				break;
			default:
				$text = $link." - ".$content;
		}
		return $text;
	}
	
	private function img ($src,$type) {
		switch ($type) {
			case "html":
				$text = "<img src=\"$src\" />";
				break;
			case "ubb":
				$text = "[img]".$src."[/img]";
				break;
			default:
				$text = $src;
		}
		return $text;
	}
	
	private function link_code($label,$content,$id=NULL) {
		return	"<label for='".$id."' title='Click to select the textbox'>$label</label>\n".
				"<input type='text' readonly='readonly' id='".$id."' name='".$id."' value='".$content."' onfocus='this.select();'></input>\n<br/>\n";
	}
}
?>
