<?php

class IcoFileHandlerTheme extends Themelet {
	public function display_image(Page $page, Image $image) {
		$ilink = make_link("get_ico/{$image->id}/{$image->id}.ico");
		$html = "
			<img id='main_image' src='$ilink'>
		";
		$page->add_block(new Block("Image", $html, "main", 10));
	}
}

