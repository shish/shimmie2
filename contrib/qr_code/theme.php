<?php
class QRImageTheme extends Themelet {
	public function links_block($link) {
		global $page;
		$page->add_block( new Block(
			"QR Code","<img src='http://chart.apis.google.com/chart?chs=150x150&cht=qr&chl=$link' />","left",50));
	}
}
?>
