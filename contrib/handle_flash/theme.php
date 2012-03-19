<?php

class FlashFileHandlerTheme extends Themelet {
	public function display_image(Page $page, Image $image) {
		$ilink = $image->get_image_link();
		// FIXME: object and embed have "height" and "width"
		$html = "
			<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000'
			        codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0'
					height='{$image->height}'
					width='{$image->width}'
					wmode='opaque'
					>
				<param name='movie' value='$ilink'/>
				<param name='quality' value='high' />
				<embed src='$ilink' quality='high'
					pluginspage='http://www.macromedia.com/go/getflashplayer'
					height='{$image->height}'
					width='{$image->width}'
					wmode='opaque'
					type='application/x-shockwave-flash'></embed>
			</object>";
		$page->add_block(new Block("Flash Animation", $html, "main", 10));
	}
}
?>
