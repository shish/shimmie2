<?php
// FIXME: Move all the stuff that handles size input to main.php
// FIXME: Move default canvas size to config file; changeable in board config
// While we're here, add maximum and minimum image sizes in config
// Maybe allow the resolution limiter extension to have a say in this 

class OekakiTheme extends Themelet {
	public function display_page() {
		global $config, $page;

		$base_href = get_base_href();

		$oekW = $config->get_int("oekaki_width", 400);
		$oekH = $config->get_int("oekaki_height", 400);
		if(isset($_POST['oekW']) && isset($_POST['oekH'])) {
			$oekW = int_escape($_POST['oekW']);
			$oekH = int_escape($_POST['oekH']);
		}

		$html = "
    <applet archive='$base_href/ext/oekaki/chibipaint.jar' code='chibipaint.ChibiPaint.class' width='800' height='600'>
      <param name='canvasWidth' value='".$oekW."' />
      <param name='canvasHeight' value='".$oekH."' />
      <param name='postUrl' value='".make_http(make_link("oekaki/upload"))."' />
      <param name='exitUrl' value='".make_http(make_link("oekaki/claim"))."' />
      <param name='exitUrlTarget' value='_self' />
      JAVA NOT INSTALLED :(<!-- alternative content for users who don't have Java installed -->
    </applet>
		";

#      <param name='loadImage' value='http://yourserver/oekaki/pictures/168.png' />
#      <param name='loadChibiFile' value='http://yourserver/oekaki/pictures/168.chi' />
		// FIXME: prevent oekaki block from collapsing on click in cerctain themes. This causes canvas reset
		$page->set_title("Oekaki");
		$page->set_heading("Oekaki");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Oekaki", $html, "main", 20));
	}

	public function display_block() {
		global $config, $page;
		//FIXME: input field alignment could be done more elegantly, without inline styling
		//FIXME: autocomplete='off' seems to be an invalid HTML tag

		$oekW = $config->get_int("oekaki_width", 400);
		$oekH = $config->get_int("oekaki_height", 400);
		if(isset($_POST['oekW']) && isset($_POST['oekH'])) {
			$oekW = int_escape($_POST['oekW']);
			$oekH = int_escape($_POST['oekH']);
		}

		$page->add_block(new Block("Oekaki", 
			"
			<form form enctype='multipart/form-data' action='".make_link("oekaki/create")."' method='POST'>
				<input autocomplete='off' style='width: auto;' name='oekW' type='text' size='3' value='".$oekW."'/>".
				"x".
				"<input autocomplete='off' style='width: auto;' name='oekH' type='text' size='3' value='".$oekH."'/>".
				"<input autocomplete='off' type='submit' value='Create!' />
			</form>
			"
			, "left", 21)); // upload is 20
	}
}

