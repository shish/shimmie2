<?php
/*
 * Name: QR Codes
 * Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com]
 * Description: Shows a QR Code for downloading an image to cell phones.
 * 				Based on Artanis's Link to Image Extension <artanis.00@gmail.com>
 * 				Includes QRcode Perl CGI & PHP scripts ver. 0.50 [http://www.swetake.com/qr/qr_cgi_e.html]
 */
class QRImage extends SimpleExtension {
	public function onDisplayingImage($event) {
		$this->theme->links_block(make_http(make_link('image/'.$event->image->id)));
	}
}
?>
