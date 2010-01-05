<?php

class ExtManagerTheme extends Themelet {
	public function display_table(Page $page, $extensions, $editable) {
		$en = $editable ? "<th>Enabled</th>" : "";
		$html = "
			<form action='".make_link("ext_manager/set")."' method='POST'>
				<script>
				$(document).ready(function() {
					$(\"#extensions\").tablesorter();
				});
				</script>
				<table id='extensions' class='zebra'>
					<thead>
						<tr>$en<th>Name</th><th>Description</th></tr>
					</thead>
					<tbody>
		";
		$n = 0;
		foreach($extensions as $extension) {
			if(!$editable && $extension->visibility == "admin") continue;

			$ext_name = $extension->ext_name;
			$h_name = empty($extension->name) ? $ext_name : html_escape($extension->name);
			$h_description = html_escape($extension->description);
			if($extension->enabled === TRUE) $h_enabled = " checked='checked'";
			else if($extension->enabled === FALSE) $h_enabled = "";
			else $h_enabled = " disabled checked='checked'";
			$h_link = make_link("ext_doc/".html_escape($extension->ext_name));
			$oe = ($n++ % 2 == 0) ? "even" : "odd";

			$en = $editable ? "<td><input type='checkbox' name='ext_$ext_name'$h_enabled></td>" : "";
			$html .= "
				<tr class='$oe'>
					$en
					<td><a href='$h_link'>$h_name</a></td>
					<td style='text-align: left;'>$h_description</td>
				</tr>";
		}
		$set = $editable ? "<tfoot><tr><td colspan='5'><input type='submit' value='Set Extensions'></td></tr></tfoot>" : "";
		$html .= "
					</tbody>
					$set
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
		$author = "";
		if($info->author) {
			if($info->email) {
				$author = "<br><b>Author:</b> <a href=\"mailto:".html_escape($info->email)."\">".html_escape($info->author)."</a>";
			}
			else {
				$author = "<br><b>Author:</b> ".html_escape($info->author);
			}
		}
		$version = ($info->version) ? "<br><b>Version:</b> ".html_escape($info->version) : "";
		$link = ($info->link) ? "<br><b>Home Page:</b> <a href=\"".html_escape($info->link)."\">Link</a>" : "";
		$doc = $info->documentation;
		$html = "
			<div style='margin: auto; text-align: left; width: 512px;'>
				$author
				$version
				$link
				<p>$doc
				<hr>
				<p><a href='".make_link("ext_manager")."'>Back to the list</a>
			</div>";

		$page->set_title("Documentation for ".html_escape($info->name));
		$page->set_heading(html_escape($info->name));
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Documentation", $html));
	}
}
?>
