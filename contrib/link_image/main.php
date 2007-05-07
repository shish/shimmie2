<?php
class LinkImage extends Extension {
	//event handler
	public function receive_event($event) {
		if(is_a($event, 'DisplayingImageEvent')) {
			global $page;
			$page->add_main_block(new Block("Link to Image", $this->get_html($event->image)));
		}
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Extension - Link to Image");
			$sb->add_text_option("ext_link-img_text-link_format","Text Link Format");
			$event->panel->add_main_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_string_from_post("ext_link-img_text-link_format");
		}
		if(is_a($event, 'InitExtEvent')) {
			global $config;
			//just set default if empty.
			if ($config->get_string("ext_link-img_text-link_format") == "") {
			$config->set_string("ext_link-img_text-link_format", '$title - $id ($ext $size $filesize)');
			}
		}
	}
	
	private function get_html ($image) {
		global $config;
		
		$thumb_src = $image->get_thumb_link();
		$image_src = $image->get_image_link();
		$post_link = $image->get_short_link();
		
		$text_link = $this->parse_link_template($config->get_string("ext_link-img_text-link_format"),$image);
		
		$html = "<div class='ntf'>";
		
		$html .= "<fieldset><legend>UBB Code</legend>";
		$html .= $this->link_code(
								"Text Link",
								$this->ubb_url($post_link, $text_link),
								"ubb_text-link");
		$html .= $this->link_code(
								"Thumbnail Link",
								$this->ubb_url($post_link, $this->ubb_img($thumb_src)),
								"ubb_thumb-link");
		$html .= $this->link_code(
								"Inline Image",
								$this->ubb_img($image_src),
								"ubb_full-img");
		$html .= "</fieldset>";
		
		$html .= "<fieldset><legend>HTML Code</legend>";
		$html .= $this->link_code(
								"Text Link",
								$this->html_url($post_link, $text_link),
								"html_text-link");
		$html .= $this->link_code(
								"Thumbnail Link",
								$this->html_url($post_link,$this->html_img($thumb_src)),
								"html_thumb-link");
		$html .= $this->link_code(
								"Inline Image",
								$this->html_img($image_src),
								"html_full-image");
		$html .= "</fieldset>";
		
		$html .= "</div>";
		
		return $html;
	}
	
	private function ubb_url($link,$content) {
		return "[url=".$link."]".$content."[/url]";
	}
	private function ubb_img($src) {
		return "[img]".$src."[/img]";
	}
	
	private function html_url($link,$content) {
		return "<a href=\"".$link."\">".$content."</a>";
	}
	private function html_img($src) {
		return "<img src=\"".$src."\" />";
	}
	
	private function link_code($label,$content,$id=NULL) {
		$control = "<label for='".$id."'>$label</label>\n";
		$control .= "<input type='text' id='".$id."' name='".$id."' value='".$content."' onfocus='this.select();'></input>\n";
		$control .= "<br/>\n\n";
		return $control;
	}
	
	private function parse_link_template($tmpl, $img) { //shamelessly copied from image.class.php
		global $config;
		
		// don't bother hitting the database if it won't be used...
		$safe_tags = "";
		if(strpos($tmpl, '$tags') !== false) { // * stabs dynamically typed languages with a rusty spoon *
			$safe_tags = preg_replace(
				"/[^a-zA-Z0-9_\- ]/",
				"", $img->get_tag_list());
		}
		
		$base_href = $config->get_string('base_href');
		$fname = $img->get_filename();
		$base_fname = strpos($fname, '.') ? substr($fname, 0, strrpos($fname, '.')) : $fname;
		
		$tmpl = str_replace('$id',   $img->id,   $tmpl);
		$tmpl = str_replace('$hash', $img->hash, $tmpl);
		$tmpl = str_replace('$tags', $safe_tags, $tmpl);
		$tmpl = str_replace('$base', $base_href, $tmpl);
		$tmpl = str_replace('$ext',  $img->ext,  $tmpl);
		$tmpl = str_replace('$size', "{$img->width}x{$img->height}", $tmpl);
		$tmpl = str_replace('$filesize', to_shorthand_int($img->filesize), $tmpl);
		$tmpl = str_replace('$filename', $base_fname, $tmpl);
		$tmpl = str_replace('$title', $config->get_string("title"), $tmpl);
		
		return $tmpl;
	}
}
add_event_listener(new LinkImage());
?>