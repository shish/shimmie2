<?php
class LinkImageHTML {
	var $post_link;
	var $image_src;
	var $thumb_src;
	var $text_link;
	
	function __construct ($post, $img, $thumb, $text) {
		$this->post_link = $post;
		$this->image_src = $img;
		$this->thumb_src = $thumb;
		$this->text_link = $text;
	}
	
	public function getHTML () {
		$html = "";

/* Rearrange or add to the code sections here.                    (BEGINNER) *
 * Please do not edit anything outside the following section.                *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
		$html .= "<div id='link_to_image'>";
		$html .= $this->BBCode();
		$html .= $this->HTML();
		$html .= $this->PlainText();
		$html .= "</div>";
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		return $html;
	}
	
/* Section Construction                                       (INTERMEDIATE) *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
 	private function BBCode() {
 		return $this->section("<a href='http://en.wikipedia.org/wiki/Bbcode' target='_blank'>BBCode</a></legend>",
 										$this->link_code("Text Link", $this->ubb_url($this->post_link, $this->text_link), "ubb_text-link").
 										$this->link_code("Thumbnail Link",$this->ubb_url($this->post_link, $this->ubb_img($this->thumb_src)),"ubb_thumb-link").
 										$this->link_code("Inline Image", $this->ubb_img($this->image_src), "ubb_full-img"));
	}
	
	private function HTML() {
		return $this->section("<a href='http://en.wikipedia.org/wiki/Html' target='_blank'>HTML</a>",
											$this->link_code("Text Link", $this->html_url($this->post_link, $this->text_link), "html_text-link").
											$this->link_code("Thumbnail Link", $this->html_url($this->post_link,$this->html_img($this->thumb_src)), "html_thumb-link").
											$this->link_code("Inline Image", $this->html_img($this->image_src), "html_full-image"));
	}
	
	private function PlainText() {
		return $this->section("Plain Text",
										$this->link_code("Post URL",$this->post_link,"text_post-link").
										$this->link_code("Thumbnail URL",$this->thumb_src,"text_thumb-url").
										$this->link_code("Image URL",$this->image_src,"text_image-src"));
	}
	
	private function section ($legend, $content) {
		return "<fieldset><legend>$legend</legend>$content</fieldset>\n\n";
	}
	
/* Text and Textbox Construction                                  (ADVANCED) *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function ubb_url($link,$content) {
		if ($content == NULL) { $content=$link; }
		return "[url=".$link."]".$content."[/url]";
	}
	private function ubb_img($src) {
		return "[img]".$src."[/img]";
	}
	
	private function html_url($link,$content) {
		if ($content == NULL) { $content=$link; }
		return "<a href=\"".$link."\">".$content."</a>";
	}
	private function html_img($src) {
		return "<img src=\"".$src."\" />";
	}
	
	private function link_code($label,$content,$id=NULL) {
		$control = "<label for='".$id."' title='Click to select the textbox'>$label</label>\n";
		$control .= "<input type='text' readonly='readonly' id='".$id."' name='".$id."' value='".$content."' onfocus='this.select();'></input>\n";
		$control .= "<br/>\n";
	return $control;
	}
}
?>