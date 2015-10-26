<?php
class BlocksTheme extends Themelet {
	public function display_blocks($blocks) {
		global $page;

		$html = "<table class='form' style='width: 100%;'>";
		foreach($blocks as $block) {
			$html .= make_form(make_link("blocks/update"));
			$html .= "<input type='hidden' name='id' value='".html_escape($block['id'])."'>";
			$html .= "<tr>";
			$html .= "<th>Title</th><td><input type='text' name='title' value='".html_escape($block['title'])."'></td>";
			$html .= "<th>Area</th><td><input type='text' name='area' value='".html_escape($block['area'])."'></td>";
			$html .= "<th>Priority</th><td><input type='text' name='priority' value='".html_escape($block['priority'])."'></td>";
			$html .= "<th>Pages</th><td><input type='text' name='pages' value='".html_escape($block['pages'])."'></td>";
			$html .= "<th>Delete</th><td><input type='checkbox' name='delete'></td>";
			$html .= "<td><input type='submit' value='Save'></td>";
			$html .= "</tr>";
			$html .= "<tr>";
			$html .= "<td colspan='11'><textarea rows='5' name='content'>".html_escape($block['content'])."</textarea></td>";
			$html .= "</tr>\n";
			$html .= "<tr>";
			$html .= "<td colspan='11'>&nbsp;</td>";
			$html .= "</tr>\n";
			$html .= "</form>\n";
		}
		$html .= make_form(make_link("blocks/add"));
			$html .= "<tr>";
			$html .= "<th>Title</th><td><input type='text' name='title' value=''></td>";
			$html .= "<th>Area</th><td><select name='area'><option>left<option>main</select></td>";
			$html .= "<th>Priority</th><td><input type='text' name='priority' value='50'></td>";
			$html .= "<th>Pages</th><td><input type='text' name='pages' value='post/list*'></td>";
			$html .= "<td colspan='3'><input type='submit' value='Add'></td>";
			$html .= "</tr>";
			$html .= "<tr>";
			$html .= "<td colspan='11'><textarea rows='5' name='content'></textarea></td>";
			$html .= "</tr>\n";
		$html .= "</form>";
		$html .= "</table>";

		$page->set_title("Blocks");
		$page->set_heading("Blocks");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Block Editor", $html));
	}
}

