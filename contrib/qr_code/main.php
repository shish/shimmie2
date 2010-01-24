<?php
/*
 * Name: QR Codes
 * Author: Zach Hall <zach@sosguy.net> [http://seemslegit.com]
 * Description: Shows a QR Code for downloading an image to cell phones.
 * 				Based on Artanis's Link to Image Extension <artanis.00@gmail.com>
 * 				Includes QRcode Perl CGI & PHP scripts ver. 0.50 [http://www.swetake.com/qr/qr_cgi_e.html]
 */
class QRImage implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);
			if(($event instanceof DisplayingImageEvent)) {
				$this->theme->links_block($page, $this->data($event->image));
			}
		
	}

	private function hostify($str) {
		$str = str_replace(" ", "%20", $str);
		if(strpos($str, "ttp://") > 0) {
			return $str;
		}
		else {
			return "http://" . $_SERVER["HTTP_HOST"] . $str;
		}
	}
	
	private function data($image) {
		global $config;
		$i_image_id = int_escape($image->id);
		return array(
			'image_src'	=> $this->hostify('/image/'.$i_image_id));
	}
}
add_event_listener(new QRImage());
?>
