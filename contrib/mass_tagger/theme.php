<?php

class MassTaggerTheme extends Themelet {
	/*
	 * Show $text on the $page
	 */
	public function display_mass_tagger( Page $page, Event $event, $config ) {
		$data_href = get_base_href();  
		$page->add_header("<link  href='$data_href/ext/mass_tagger/mass_tagger.css' type='text/css' rel='stylesheet' />");
		$page->add_header("<script src='$data_href/ext/mass_tagger/mass_tagger.js' type='text/javascript'></script>");
		$body = "
			<form action='".make_link("mass_tagger/tag")."' method='POST'>
				<input id='mass_tagger_activate' type='button' onclick='activate_mass_tagger(\"$data_href\");' value='Activate'/>
				<div id='mass_tagger_controls'>
					Click on images to mark them. Use the 'Index Options' in the Board Config to increase the amount of shown images.
					<br />
					<input type='hidden' name='ids' id='mass_tagger_ids' />
					<label>Tags: <input type='text' name='tag' /></label>
					
					<input type='submit' value='Tag Marked Images' />
				</div>
			</form>
		";
		$block = new Block("Mass Tagger", $body, "left", 50);
		$page->add_block( $block );
	}
}
?>
