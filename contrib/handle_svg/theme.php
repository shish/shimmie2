<?php

class SVGFileHandlerTheme extends Themelet {
	public function display_image(Page $page, Image $image) {
		$ilink = make_link("get_svg/{$image->id}/{$image->id}.svg");
//		$ilink = $image->get_image_link();
		$html = "
			<object data='$ilink' type='image/svg+xml' width='{$image->width}' height='{$image->height}'>
			    <embed src='$ilink' type='image/svg+xml' width='{$image->width}' height='{$image->height}' />
			</object>
		";
		$page->add_block(new Block("Image", $html, "main", 0));
	}
}
?>
