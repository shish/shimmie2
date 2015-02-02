<?php
class QRImageTheme extends Themelet {
	/**
	 * @param string $link
	 */
	public function links_block($link) {
		global $page;

		$protocol = is_https_enabled() ? "https://" : "http://";

		$page->add_block( new Block(
			"QR Code","<img alt='QR Code' src='{$protocol}chart.apis.google.com/chart?chs=150x150&amp;cht=qr&amp;chl={$link}' />","left",50));
	}
}

