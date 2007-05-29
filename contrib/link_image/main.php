<?php
/*
 * This file may not be distributed without its readme.txt
**/
class LinkImage extends Extension {
	//event handler
	public function receive_event($event) {
		if(is_a($event, 'DisplayingImageEvent')) {
			global $page;
			global $config;
			$data_href = $config->get_string("data_href");
			$page->add_header("<link rel='stylesheet' href='$data_href/ext/link_image/_style.css' type='text/css'>",0);
			$page->add_main_block(new Block("Link to Image", $this->get_html($event->image)));
		}
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Link to Image");
			//$sb->add_label("Text link format: ");
			$sb->add_text_option("ext_link-img_text-link_format","Text Link Format:");
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
	
	private function get_html($image) {
		global $config;
		
		$thumb_src = $image->get_thumb_link();
		$image_src = $image->get_image_link();
		$post_link = $image->get_short_link();
		$text_link = $this->parse_link_template($config->get_string("ext_link-img_text-link_format"),$image);
		
		$html = "";
		
		if($this->get_HTML_PHP()) {
			$html_gen = new LinkImageHTML($post_link, $image_src, $thumb_src, $text_link);
			$html = $html_gen->getHTML();
		}
		
		return $html;
	}

/* This function would do better generalized in the Extension class instead  *
 * of repeated in every extension. And probaly renamed, too...               *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function get_HTML_PHP() {
		global $config;
		$theme = $config->get_string("theme");
	
		if(file_exists("themes/$theme/link_image.html.php")) {
			//$html .= "Using theme version";
			include "themes/$theme/link_image.html.php";
		} else if(file_exists("ext/link_image/link_image.html.php")) {
			include "ext/link_image/link_image.html.php";
			//$html .= "Using default generation in absense of themed generation.";
		} else {
			echo "<b>[Link to Image]<b> Error: <b>link_image.html.php</b> not found at either <b>ext/link_image/link_image.html.php</b> nor <b>themes/$theme/link_image.html.php</b>.<br/>".
							"Please restore the default file to the former location, and copy it over to the latter if you wish to edit the html output of this extension.";
			return false;
		}		
		return true;
	}
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	
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
