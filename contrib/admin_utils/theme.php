<?php

class AdminUtilsTheme extends Themelet {
	public function display_form($page) {
		$html = "
			<p><form action='".make_link("admin_utils")."' method='POST'>
				<select name='action'>
					<option value='lowercase all tags'>All tags to lowercase</option>
					<option value='recount tag use'>Recount tag use</option>
					<option value='purge unused tags'>Purge unused tags</option>
				</select>
				<input type='submit' value='Go'>
			</form>
		";
		$page->add_block(new Block("Misc Admin Tools", $html));
	}
}
?>
