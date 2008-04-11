<?php

class IcoFileHandlerTheme extends Themelet {
	public function display_image($page, $image) {
		$ilink = $image->get_image_link();
		$width = $image->width;
		$height = $image->height;
		$html = "
			<object data='$ilink' type='image/x-icon' width='$width' height='$height'>
			    <embed src='$ilink' type='image/x-icon' width='$width' height='$height' />
			</object>
		";
		$page->add_block(new Block("Image", $html, "main", 0));
	}
}
?>
