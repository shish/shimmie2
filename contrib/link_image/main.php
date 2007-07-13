<?php
class LinkImage extends Extension {
	var $theme;
	
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("link_image", "LinkImageTheme");
			if(is_a($event, 'DisplayingImageEvent')) {
				global $page;
				global $config;
				$data_href = $config->get_string("data_href");
				$page->add_header("<link rel='stylesheet' href='$data_href/ext/link_image/_style.css' type='text/css'>",0);
				
				$this->theme->links_block($page,$this->data($event->image));
			}
			if(is_a($event, 'SetupBuildingEvent')) {
				$sb = new SetupBlock("Link to Image");
				$sb->add_text_option("ext_link-img_text-link_format", "Text Link Format");
				$event->panel->add_block($sb);
			}
			if(is_a($event, 'InitExtEvent')) {
				global $config;
				//just set default if empty.
				if ($config->get_string("ext_link-img_text-link_format") == "") {
					$config->set_string("ext_link-img_text-link_format",
										'$title - $id ($ext $size $filesize)');
				}
			}
		}
	private function data($image) {
		global $config;
		
		$text_link = $this->parse_link_template($config->get_string("ext_link-img_text-link_format"),$image);
		$text_link = $text_link==" "? null : $text_link; // null blank setting so the url gets filled in on the text links.
		
		return array(
			'thumb_src'	=>	$image->get_thumb_link(),
			'image_src'	=>	$image->get_image_link(),
			'post_link'	=>	$image->get_short_link(),
			'text_link'		=>	$text_link);
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
