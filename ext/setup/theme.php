<?php

class SetupTheme extends Themelet {
	/*
	 * Display a set of setup option blocks
	 *
	 * $panel = the container of the blocks
	 * $panel->blocks the blocks to be displayed, unsorted
	 *
	 * It's recommented that the theme sort the blocks before doing anything
	 * else, using:  usort($panel->blocks, "blockcmp");
	 *
	 * The page should wrap all the options in a form which links to setup_save
	 */
	public function display_page(Page $page, SetupPanel $panel) {
		$setupblock_html1 = "";
		$setupblock_html2 = "";

		usort($panel->blocks, "blockcmp");

		/*
		 * Try and keep the two columns even; count the line breaks in
		 * each an calculate where a block would work best
		 */
		$len1 = 0;
		$len2 = 0;
		foreach($panel->blocks as $block) {
			if($block instanceof SetupBlock) {
				$html = $this->sb_to_html($block);
				$len = count(explode("<br>", $html))+1;
				if($len1 <= $len2) {
					$setupblock_html1 .= $this->sb_to_html($block);
					$len1 += $len;
				}
				else {
					$setupblock_html2 .= $this->sb_to_html($block);
					$len2 += $len;
				}
			}
		}

		$table = "
			<form action='".make_link("setup/save")."' method='POST'><table>
			<tr><td>$setupblock_html1</td><td>$setupblock_html2</td></tr>
			<tr><td colspan='2'><input type='submit' value='Save Settings'></td></tr>
			</table></form>
			";

		$page->set_title("Shimmie Setup");
		$page->set_heading("Shimmie Setup");
		$page->add_block(new Block("Navigation", $this->build_navigation(), "left", 0));
		$page->add_block(new Block("Setup", $table));
	}

	public function display_advanced(Page $page, $options) {
		$rows = "";
		foreach($options as $name => $value) {
			$h_value = html_escape($value);
			$len = strlen($h_value);
			$box = "";
			if($len < 50) {
				$box .= "<input type='text' name='_config_$name' value='$h_value'>";
			}
			else {
				$box .= "<textarea cols='50' rows='4' name='_config_$name'>$h_value</textarea>";
			}
			$box .= "<input type='hidden' name='_type_$name' value='string'>";
			$rows .= "<tr><td>$name</td><td>$box</td></tr>";
		}

		$table = "
			<form action='".make_link("setup/save")."' method='POST'><table>
			<tr><th width='25%'>Name</th><th>Value</th></tr>
			$rows
			<tr><td colspan='2'><input type='submit' value='Save Settings'></td></tr>
			</table></form>
			";

		$page->set_title("Shimmie Setup");
		$page->set_heading("Shimmie Setup");
		$page->add_block(new Block("Navigation", $this->build_navigation(), "left", 0));
		$page->add_block(new Block("Setup", $table));
	}

	protected function build_navigation() {
		return "
			<a href='".make_link()."'>Index</a>
			<br><a href='http://trac.shishnet.org/shimmie2/wiki/Settings'>Help</a>
			<br><a href='".make_link("setup/advanced")."'>Advanced</a>
		";
	}

	protected function sb_to_html(SetupBlock $block) {
		return "<div class='setupblock'><b>{$block->header}</b><br>{$block->body}</div>\n";
	}
}
?>
