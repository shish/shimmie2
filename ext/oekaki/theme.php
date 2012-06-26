<?php
// FIXME: Move all the stuff that handles size input to main.php
// FIXME: Move default canvas size to config file; changeable in board config
// While we're here, add maximum and minimum image sizes in config
// Maybe allow the resolution limiter extension to have a say in this 
$defOekW = 400; // Common default for oekaki boards: 300x300
$defOekH = 400;
class OekakiTheme extends Themelet {
	public function display_page() {
		global $config, $page, $defOekW, $defOekH;

		$base_href = get_base_href();
		$http_base = make_http($base_href);

		if (isset($_POST['oekW']) and isset($_POST['oekH'])){
			$oekW = $_POST['oekW'];
			$oekH = $_POST['oekH'];
			if(!ctype_digit($oekW) or !ctype_digit($oekH)){
				$oekW = $defOekW;
				$oekH = $defOekH;
			}
		} else{
			$oekW = $defOekW;
			$oekH = $defOekH;
		}
		
		$html = "
    <applet archive='$base_href/ext/oekaki/chibipaint.jar' code='chibipaint.ChibiPaint.class' width='800' height='600'>
      <param name='canvasWidth' value='".$oekW."' />
      <param name='canvasHeight' value='".$oekH."' />
      <param name='postUrl' value='".make_http(make_link("oekaki/upload"))."' />
      <param name='exitUrl' value='".make_http(make_link("oekaki/claim"))."' />
      <param name='exitUrlTarget' value='_self' />
      JAVA NOT INSTALLED >:(<!-- alternative content for users who don't have Java installed -->
    </applet>
		";

#      <param name='loadImage' value='http://yourserver/oekaki/pictures/168.png' />
#      <param name='loadChibiFile' value='http://yourserver/oekaki/pictures/168.chi' />
		// FIXME: prevent oekaki block from collapsing on click in cerctain themes. This causes canvas reset
		$page->set_title("Oekaki");
		$page->set_heading("Oekaki");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Oekaki", $html, "main", 20));
		$page->add_block(new Block(null, 
			"
			Change canvas size.
			<br>
			<form form enctype='multipart/form-data' action='".make_link("oekaki/create")."' method='POST'>
				<div style='display: inline; margin: 0; width: auto;'>
					<input autocomplete='off' style='display: inline; margin: 0; width: auto;font-size: 90%' name='oekW' type='text' size='3' value='".$oekW."'/>
					x
					<input autocomplete='off' style='display: inline; margin: 0; width: auto;font-size: 90%' name='oekH' type='text' size='3' value='".$oekH."'/>
					<input autocomplete='off' style='display: inline; margin: 0; width: auto;font-size: 90%' type='submit' value='Go!' />
				</div>
			</form>
			<br>
			<b>WARNING: Resets canvas!</b>
			"
			, "left", 21)); // upload is 20
	}

	public function display_block() {
		global $page, $defOekW, $defOekH;
		//FIXME: input field alignment could be done more elegantly, without inline styling
		//FIXME: autocomplete='off' seems to be an invalid HTML tag
		$page->add_block(new Block(null, 
			"
			<b>Oekaki</b>
			<br>
			<form form enctype='multipart/form-data' action='".make_link("oekaki/create")."' method='POST'>
				<div style='display: inline; margin: 0; width: auto;'>
					<input autocomplete='off' style='display: inline; margin: 0; width: auto; font-size: 90%' name='oekW' type='text' size='3' value='".$defOekW."'/>
					x
					<input autocomplete='off' style='display: inline; margin: 0; width: auto; font-size: 90%' name='oekH' type='text' size='3' value='".$defOekH."'/>
					<input autocomplete='off' style='display: inline; margin: 0; width: auto; font-size: 90%' type='submit' value='Go!' />
				</div>
			</form>
			"
			, "left", 21)); // upload is 20
	}
}
?>
