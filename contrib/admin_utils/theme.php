<?php

class AdminUtilsTheme extends Themelet {
	public function display_form() {
		$html = "
			<p><form action='".make_link("admin_utils")."' method='POST'>
				<input type='hidden' name='action' value='lowercase all tags'>
				<input type='submit' value='Lowercase All Tags'>
			</form>
		";
		$page->add_block(new Block("Misc Admin Tools", $html));
	}
}
?>
