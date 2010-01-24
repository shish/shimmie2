<?php
class QRImageTheme extends Themelet {
	public function links_block(Page $page, $data) {
		$image_src = $data['image_src'];
		global $config, $user;
		$base_href = $config->get_string('base_href');
		$data_href = get_base_href();
		$page->add_block( new Block(
			"QR Code","<img src='".$data_href."/ext/qr_code/qr_img.php?d=".$image_src."&s=3' />","left",50));
	}
}
?>
