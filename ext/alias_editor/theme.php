<?php

class AliasEditorTheme extends Themelet {
	/*
	 * Show a page of aliases:
	 *
	 * $aliases = an array of ($old_tag => $new_tag)
	 * $is_admin = whether things like "add new alias" should be shown
	 */
	public function display_aliases(Page $page, $aliases, $is_admin) {
		if($is_admin) {
			$action = "<th width='10%'>Action</th>";
			$add = "
				<tr>
					<form action='".make_link("alias/add")."' method='POST'>
						<td><input type='text' name='oldtag'></td>
						<td><input type='text' name='newtag'></td>
						<td><input type='submit' value='Add'></td>
					</form>
				</tr>
			";
		}
		else {
			$action = "";
			$add = "";
		}

		$h_aliases = "";
		$n = 0;
		foreach($aliases as $old => $new) {
			$h_old = html_escape($old);
			$h_new = html_escape($new);
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			
			$h_aliases .= "<tr class='$oe'><td>$h_old</td><td>$h_new</td>";
			if($is_admin) {
				$h_aliases .= "
					<td>
						<form action='".make_link("alias/remove")."' method='POST'>
							<input type='hidden' name='oldtag' value='$h_old'>
							<input type='submit' value='Remove'>
						</form>
					</td>
				";
			}
			$h_aliases .= "</tr>";
		}
		$html = "
			<script>
			$(document).ready(function() {
				$(\"#aliases\").tablesorter();
			});
			</script>
			<table id='aliases' class='zebra'>
				<thead><tr><th>From</th><th>To</th>$action</tr>$add</thead>
				<tbody>$h_aliases</tbody>
				<tfoot>$add</tfoot>
			</table>
			<p><a href='".make_link("alias/export/aliases.csv")."'>Download as CSV</a></p>
			<form enctype='multipart/form-data' action='".make_link("alias/import")."' method='POST'>
				<input type='file' name='alias_file'>
				<input type='submit' value='Upload List'>
			</form>
		";

		$page->set_title("Alias List");
		$page->set_heading("Alias List");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Aliases", $html));
	}
}
?>
