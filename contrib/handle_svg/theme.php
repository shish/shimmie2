<?php

class SVGFileHandlerTheme extends Themelet {
	public function display_image($page, $image) {
		$link = make_link("get_svg/{$image->id}/{$image->id}.svg");
		// FIXME: object and embed have "height" and "width"
		$html = "
			<object data='$link' type='image/svg+xml' width='300' height='300' />
			    <embed src='$link' type='image/svg+xml' width='300' height='300' />
			</object>
		";
		$page->add_block(new Block("Image", $html, "main", 0));
	}
}
?>
