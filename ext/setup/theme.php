<?php

class SetupTheme extends Themelet {
	public function display_not_admin($page) {
		$page->set_title("Error");
		$page->set_heading("Error");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Permission Denied", "This page is for admins only"));
	}

	public function display_page($page, $panel) {
		$setupblock_html1 = "";
		$setupblock_html2 = "";

		usort($panel->blocks, "blockcmp");

		/*
		$flip = true;
		foreach($panel->mainblocks as $block) {
			if(is_a($block, 'SetupBlock')) {
				if($flip) $setupblock_html1 .= $this->sb_to_html($block);
				else $setupblock_html2 .= $this->sb_to_html($block);
				$flip = !$flip;
			}
		}
		*/

		/*
		 * Try and keep the two columns even; count the line breaks in
		 * each an calculate where a block would work best
		 */
		$len1 = 0;
		$len2 = 0;
		foreach($panel->blocks as $block) {
			if(is_a($block, 'SetupBlock')) {
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

	private function build_navigation() {
		return "
			<a href='".make_link()."'>Index</a>
			<br><a href='http://trac.shishnet.org/shimmie/wiki/Settings'>Help</a>
		";
	}

	private function sb_to_html($block) {
		return "<div class='setupblock'><b>{$block->header}</b><br>{$block->body}</div>\n";
	}
}
?>
