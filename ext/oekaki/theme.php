<?php

class OekakiTheme extends Themelet {
	public function display_page() {
		global $config, $page;

		$base_href = get_base_href();
		$http_base = make_http($base_href);

		$html = "
    <applet archive='$base_href/ext/oekaki/chibipaint.jar' code='chibipaint.ChibiPaint.class' width='800' height='600'>
      <param name='canvasWidth' value='400' />
      <param name='canvasHeight' value='300' />
      <param name='postUrl' value='".make_http(make_link("oekaki/upload"))."' />
      <param name='exitUrl' value='".make_http(make_link("oekaki/claim"))."' />
      <param name='exitUrlTarget' value='_self' />
      JAVA NOT SUPPORTED! <!-- alternative content for users who don't have Java installed -->
    </applet>
		";

#      <param name='loadImage' value='http://yourserver/oekaki/pictures/168.png' />
#      <param name='loadChibiFile' value='http://yourserver/oekaki/pictures/168.chi' />
		
		$page->set_title("Oekaki");
		$page->set_heading("Oekiaki");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Oekaki", $html, "main", 20));
	}

	public function display_block() {
		global $page;
		$page->add_block(new Block(null, "<a href='".make_link("oekaki/create")."'>Open Oekaki</a>", "left", 21)); // upload is 20
	}
}
?>
