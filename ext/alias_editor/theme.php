<?php

class AliasEditorTheme extends Themelet {
	/*
	 * Show a page of aliases:
	 *
	 * $aliases = an array of ($old_tag => $new_tag)
	 * $is_admin = whether things like "add new alias" should be shown
	 */
	public function display_aliases($page, $aliases, $is_admin) {
		if($is_admin) {
			$action = "<td>Action</td>";
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
		foreach($aliases as $old => $new) {
			$h_old = html_escape($old);
			$h_new = html_escape($new);
			$h_aliases .= "<tr><td>$h_old</td><td>$h_new</td>";
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
			<table border='1'>
				<thead><td>From</td><td>To</td>$action</thead>
				$h_aliases
				$add
			</table>
			<p><a href='".make_link("alias/export/aliases.csv")."'>Download as CSV</a></p>
		";
		
		$page->set_title("Alias List");
		$page->set_heading("Alias List");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Aliases", $html));
	}
}
?>
