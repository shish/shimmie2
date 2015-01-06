<?php

class MassTaggerTheme extends Themelet {
	/*
	 * Show $text on the $page
	 */
	public function display_mass_tagger( Page $page, Event $event, $config ) {
		$data_href = get_base_href();  
		$body = "
			<form action='".make_link("mass_tagger/tag")."' method='POST'>
				<input id='mass_tagger_activate' type='button' onclick='activate_mass_tagger(\"$data_href\");' value='Activate'/>
				<div id='mass_tagger_controls' style='display: none;'>
					Click on images to mark them. Use the 'Index Options' in the Board Config to increase the amount of shown images.
					<br />
					<input type='hidden' name='ids' id='mass_tagger_ids' />
					Set instead of add? <input type='checkbox' name='setadd' value='set' />
					<label>Tags: <input type='text' name='tag' class='autocomplete_tags' autocomplete='off' /></label>

					<input type='submit' value='Tag Marked Images' />
				</div>
			</form>
		";
		$block = new Block("Mass Tagger", $body, "left", 50);
		$page->add_block( $block );
	}
}

