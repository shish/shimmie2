<?php
class LinkImageTheme extends Themelet {
	public function links_block(Page $page, $data) {
		$thumb_src = $data['thumb_src'];
		$image_src = $data['image_src'];
		$post_link = $data['post_link'];
		$text_link = $data['text_link'];


		$page->add_block( new Block(
			"Link to Image",
			"
			<table><tr>

			<td><fieldset>
				<legend><a href='http://en.wikipedia.org/wiki/Bbcode' target='_blank'>BBCode</a></legend>
				<table>
				".
					$this->link_code("Link",$this->url($post_link, $text_link,"ubb"),"ubb_text-link").
					$this->link_code("Thumb",$this->url($post_link, $this->img($thumb_src,"ubb"),"ubb"),"ubb_thumb-link").
					$this->link_code("Image", $this->img($image_src,"ubb"), "ubb_full-img").
				"
				</table>
			</fieldset></td>

			<td><fieldset>
				<legend><a href='http://en.wikipedia.org/wiki/Html' target='_blank'>HTML</a></legend>
				<table>
				".
					$this->link_code("Link", $this->url($post_link, $text_link,"html"), "html_text-link").
					$this->link_code("Thumb", $this->url($post_link,$this->img($thumb_src,"html"),"html"), "html_thumb-link").
					$this->link_code("Image", $this->img($image_src,"html"), "html_full-image").
				"
				</table>
			</fieldset></td>

			<td><fieldset>
				<legend>Plain Text</legend>
				<table>
				".
					$this->link_code("Link",$post_link,"text_post-link").
					$this->link_code("Thumb",$thumb_src,"text_thumb-url").
					$this->link_code("Image",$image_src,"text_image-src").
				"
				</table>
			</fieldset></td>

			</tr></table>
			",
			"main",
			50));
	}

	protected function url (/*string*/ $url, /*string*/ $content, /*string*/ $type) {
		if ($content == NULL) {$content=$url;}

		switch ($type) {
			case "html":
				$text = "<a href=\"".$url."\">".$content."</a>";
				break;
			case "ubb":
				$text = "[url=".$url."]".$content."[/url]";
				break;
			default:
				$text = $url." - ".$content;
		}
		return $text;
	}

	protected function img (/*string*/ $src, /*string*/ $type) {
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

	protected function link_code(/*string*/ $label, /*string*/ $content, $id=NULL) {
		return	"
			<tr>
				<td><label for='".$id."' title='Click to select the textbox'>$label</label></td>
				<td><input type='text' readonly='readonly' id='".$id."' name='".$id."' value='".html_escape($content)."' onfocus='this.select();' /></td>
			</tr>
		";
	}
}

