<?php
class QRImageTheme extends Themelet {
	public function links_block($link) {
		global $page;
		$base_href = get_base_href();
		$page->add_block( new Block(
			"QR Code","<img src='$base_href/ext/qr_code/qr_img.php?d=$link&s=3' />","left",50));
	}
}
?>
