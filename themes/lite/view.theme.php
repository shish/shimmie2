<?php

class CustomViewImageTheme extends ViewImageTheme {
	public function display_page($page, $image, $editor_parts) {
		$page->set_title("Image {$image->id}: ".html_escape($image->get_tag_list()));
		$page->set_heading(html_escape($image->get_tag_list()));
		$page->add_block(new Block("Navigation", $this->build_navigation($image), "left", 0));
		$page->add_block(new Block("Statistics", $this->build_stats($image), "left", 10));
		$page->add_block(new Block(null, $this->build_image_editor($image, $editor_parts), "main", 10));
		$page->add_block(new Block(null, $this->build_pin($image), "main", 11));
		$page->add_block(new Block(null, "hello world", "subheading", 0));
	}
	
	private function build_stats($image) {
		$h_owner = html_escape($image->get_owner()->name);
		$h_ownerlink = "<a href='".make_link("user/$h_owner")."'>$h_owner</a>";
		$h_ip = html_escape($image->owner_ip);
		$h_date = autodate($image->posted);
		$h_filesize = to_shorthand_int($image->filesize);

		global $user;
		if($user->is_admin()) {
			$h_ownerlink .= " ($h_ip)";
		}

		$html = "
		Id: {$image->id}
		<br>Posted: $h_date by $h_ownerlink
		<br>Size: {$image->width}x{$image->height}
		<br>Filesize: $h_filesize
		";

		if(!is_null($image->source)) {
			$h_source = html_escape($image->source);
			if(substr($image->source, 0, 7) != "http://") {
				$h_source = "http://" . $h_source;
			}
			$html .= "<br>Source: <a href='$h_source'>link</a>";
		}

		return $html;
	}
}
?>
