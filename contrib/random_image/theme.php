<?php

class RandomImageTheme extends Themelet {
	public function display_random(Page $page, Image $image) {
		$page->add_block(new Block("Random Image", $this->build_thumb_html($image), "left", 8));
	}
}
?>
