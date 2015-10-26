<?php
class NotATagTheme extends Themelet {
	public function display_untags(Page $page, $page_number, $page_count, $bans) {
		$h_bans = "";
		foreach($bans as $ban) {
			$h_bans .= "
				<tr>
					".make_form(make_link("untag/remove"))."
						<td width='30%'>{$ban['tag']}</td>
						<td>{$ban['redirect']}</td>
						<td width='10%'>
							<input type='hidden' name='tag' value='{$ban['tag']}'>
							<input type='submit' value='Remove'>
						</td>
					</form>
				</tr>
			";
		}
		$html = "
			<table id='image_bans' class='zebra sortable'>
				<thead>
					<th>Tag</th><th>Redirect</th><th>Action</th>
					<tr>
						<form action='".make_link("untag/list/1")."' method='GET'>
							<td><input type='text' name='tag' class='autocomplete_tags' autocomplete='off'></td>
							<td><input type='text' name='redirect'></td>
							<td><input type='submit' value='Search'></td>
						</form>
					</tr>
				</thead>
				$h_bans
				<tfoot><tr>
					".make_form(make_link("untag/add"))."
						<td><input type='text' name='tag' class='autocomplete_tags' autocomplete='off'></td>
						<td><input type='text' name='redirect'></td>
						<td><input type='submit' value='Ban'></td>
					</form>
				</tr></tfoot>
			</table>
		";

		$prev = $page_number - 1;
		$next = $page_number + 1;

		$h_prev = ($page_number <= 1) ? "Prev" : "<a href='".make_link("untag/list/$prev")."'>Prev</a>";
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_next = ($page_number >= $page_count) ? "Next" : "<a href='".make_link("untag/list/$next")."'>Next</a>";

		$nav = "$h_prev | $h_index | $h_next";

		$page->set_title("UnTags");
		$page->set_heading("UnTags");
		$page->add_block(new Block("Edit UnTags", $html));
		$page->add_block(new Block("Navigation", $nav, "left", 0));
		$this->display_paginator($page, "untag/list", null, $page_number, $page_count);
	}
}

