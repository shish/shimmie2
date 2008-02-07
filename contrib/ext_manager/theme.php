<?php

class ExtManagerTheme extends Themelet {
	public function display_table($page, $extensions) {
		$html = "
			<form action='".make_link("ext_manager/set")."' method='POST'>
				<table border='1'>
					<tr><th>Name</th><th>Author</th><th>Description</th><th>Enabled</th></tr>
		";
		foreach($extensions as $extension) {
			$ext_name = $extension["ext_name"];
			$h_name = empty($extension["name"]) ? $ext_name : html_escape($extension["name"]);
			$h_email = html_escape($extension["email"]);
			$h_link = isset($extension["link"]) ? html_escape($extension["link"]) : "";
			$h_author = html_escape($extension["author"]);
			$h_description = html_escape($extension["description"]);
			$h_enabled = $extension["enabled"] ? " checked='checked'" : "";
			$html .= "
				<tr>
					" . (
						empty($h_link) ? 
							"<td>$h_name</td>" :
							"<td><a href='$h_link'>$h_name</a></td>"
					) . (
						empty($h_email) ?
							"<td>$h_author</td>" :
							"<td><a href='mailto:$h_email'>$h_author</a></td>"
					) . "
					<td>$h_description</td>
					<td>
						<input type='checkbox' name='ext_$ext_name'$h_enabled>
					</td>
				</tr>";
		}
		$html .= "
					<tr><td colspan='4'><input type='submit' value='Set Extensions'></td></tr>
				</table>
			</form>
		";

		$page->set_title("Extensions");
		$page->set_heading("Extensions");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Extension Manager", $html));
	}
}
?>
