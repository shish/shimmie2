<?php
/*
 * Name: QR Codes
 * Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com]
 * Description: Shows a QR Code for downloading an image to cell phones.
 * 				Based on Artanis's Link to Image Extension <artanis.00@gmail.com>
 *              Further modified by Shish to remove the 7MB local QR generator
 *              and replace it with a link to google chart APIs
 */
class QRImage extends Extension {
	public function onDisplayingImage(DisplayingImageEvent $event) {
		$this->theme->links_block(make_http(make_link('image/'.$event->image->id.'.jpg')));
	}
}
?>
