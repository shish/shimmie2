<?php
class QRImageTheme extends Themelet {
	public function links_block($link) {
		global $page;
		$page->add_block( new Block(
			"QR Code","<img alt='QR Code' src='http://chart.apis.google.com/chart?chs=150x150&amp;cht=qr&amp;chl=$link' />","left",50));
	}
}
?>
