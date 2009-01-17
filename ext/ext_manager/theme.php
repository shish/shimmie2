<?php

class ExtManagerTheme extends Themelet {
	public function display_table(Page $page, $extensions) {
		$html = "
			<form action='".make_link("ext_manager/set")."' method='POST'>
				<table border='1'>
					<tr><th>Name</th><th>Author</th><th>Description</th><th>Links</th><th>Enabled</th></tr>
		";
		foreach($extensions as $extension) {
			$ext_name = $extension->ext_name;
			$h_name = empty($extension->name) ? $ext_name : html_escape($extension->name);
			$h_email = html_escape($extension->email);
			$h_link = isset($extension->link) ?
					"<a href=\"".html_escape($extension->link)."\">Info</a>" : "";
			$h_doc = isset($extension->documentation) ?
					"<a href=\"".make_link("ext_doc/".html_escape($extension->ext_name))."\">Help</a>" : "";
			$h_author = html_escape($extension->author);
			$h_description = html_escape($extension->description);
			$h_enabled = $extension->enabled ? " checked='checked'" : "";

			$html .= "
				<tr>
					<td>$h_name</td>
					" . (
						empty($h_email) ?
							"<td>$h_author</td>" :
							"<td><a href='mailto:$h_email'>$h_author</a></td>"
					) . "
					<td>$h_description</td>
					<td>$h_link $h_doc</td>
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

	public function display_blocks(Page $page, $extensions) {
		$n = 0;
		$col_1 = "";
		$col_2 = "";
		foreach($extensions as $extension) {
			$ext_name = $extension->ext_name;
			$h_name = empty($extension->name) ? $ext_name : html_escape($extension->name);
			$h_email = html_escape($extension->email);
			$h_link = isset($extension->link) ?
					"<a href=\"".html_escape($extension->link)."\">Original Site</a>" : "";
			$h_doc = isset($extension->documentation) ?
					"<a href=\"".make_link("ext_doc/".html_escape($extension->ext_name))."\">Documentation</a>" : "";
			$h_author = html_escape($extension->author);
			$h_description = html_escape($extension->description);
			$h_enabled = $extension->enabled ? " checked='checked'" : "";
			$h_author_link = empty($h_email) ?
					"$h_author" :
					"<a href='mailto:$h_email'>$h_author</a>";

			$html = "
				<p><table border='1'>
					<tr>
						<th colspan='2'>$h_name</th>
					</tr>
					<tr>
						<td>By $h_author_link</td>
						<td width='25%'>Enabled:&nbsp;<input type='checkbox' name='ext_$ext_name'$h_enabled></td>
					</tr>
					<tr>
						<td style='text-align: left' colspan='2'>$h_description<p>$h_link $h_doc</td>
					</tr>
				</table>
			";
			if($n++ % 2 == 0) {
				$col_1 .= $html;
			}
			else {
				$col_2 .= $html;
			}
		}
		$html = "
			<form action='".make_link("ext_manager/set")."' method='POST'>
				<table border='0'>
					<tr><td width='50%'>$col_1</td><td>$col_2</td></tr>
					<tr><td colspan='2'><input type='submit' value='Set Extensions'></td></tr>
				</table>
			</form>
		";

		$page->set_title("Extensions");
		$page->set_heading("Extensions");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Extension Manager", $html));
	}

	public function display_doc(Page $page, ExtensionInfo $info) {
		$html = "<div style='margin: auto; text-align: left; width: 512px;'>".$info->documentation."</div>";
		$page->set_title("Documentation for ".html_escape($info->name));
		$page->set_heading(html_escape($info->name));
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Documentation", $html));
	}
}
?>
