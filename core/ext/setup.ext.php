<?php

/* SetupBuildingEvent {{{
 *
 * Sent when the setup page is ready to be added to
 */
class SetupBuildingEvent extends Event {
	var $panel;

	public function SetupBuildingEvent($panel) {
		$this->panel = $panel;
	}

	public function get_panel() {
		return $this->panel;
	}
}
// }}}
/* SetupPanel {{{
 *
 */
class SetupPanel extends Page {
}
// }}}
/* SetupBlock {{{
 *
 */
class SetupBlock extends Block {
	var $header;
	var $body;

	public function SetupBlock($title) {
		$this->header = $title;
	}

	public function add_label($text) {
		$this->body .= $text;
	}
	public function add_text_option($name) {
		global $config;
		$val = $config->get_string($name);
		$this->body .= "<input type='text' name='$name' value='$val'>\n";
	}
	public function add_longtext_option($name) {
		global $config;
		$val = $config->get_string($name);
		$this->body .= "<textarea rows='5' cols='40' name='$name'>$val</textarea>\n";
		$this->body .= "<!--<br><br><br><br>-->\n"; // setup page auto-layout counts <br> tags
	}
	public function add_bool_option($name) {
		global $config;
		$checked = $config->get_bool($name) ? " checked" : "";
		$this->body .= "<input type='checkbox' name='$name'$checked>\n";
	}
	public function add_hidden_option($name) {
		global $config;
		$val = $config->get_string($name);
		$this->body .= "<input type='hidden' name='$name' value='$val'>";
	}
	public function add_int_option($name) {
		global $config;
		$val = $config->get_string($name);
		$this->body .= "<input type='text' name='$name' value='$val' size='4' style='text-align: center;'>\n";
	}
	public function add_shorthand_int_option($name) {
		global $config;
		$val = to_shorthand_int($config->get_string($name));
		$this->body .= "<input type='text' name='$name' value='$val' size='6' style='text-align: center;'>\n";
	}
	public function add_choice_option($name, $options) {
		global $config;
		$current = $config->get_string($name);
		
		$html = "<select name='$name'>";
		foreach($options as $optname => $optval) {
			if($optval == $current) $selected=" selected";
			else $selected="";
			$html .= "<option value='$optval'$selected>$optname</option>\n";
		}
		$html .= "</select>";

		$this->body .= $html;
	}
}
// }}}

class Setup extends Extension {
// event handling {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "setup")) {
			global $user;
			if(!$user->is_admin()) {
				global $page;
				$page->set_title("Error");
				$page->set_heading("Error");
				$page->add_side_block(new NavBlock(), 0);
				$page->add_main_block(new Block("Permission Denied", "This page is for admins only"), 0);
			}
			else {
				if($event->get_arg(0) == "save") {
					global $config;
					send_event(new ConfigSaveEvent($config));
					$config->save();
					
					global $page;
					$page->set_mode("redirect");
					$page->set_redirect(make_link("setup"));
				}
				else {
					$panel = new SetupPanel();
					send_event(new SetupBuildingEvent($panel));
					$this->build_page($panel);
				}
			}
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$themes = array();
			foreach(glob("themes/*") as $theme_dirname) {
				$name = str_replace("themes/", "", $theme_dirname);
				$themes[ucfirst($name)] = $name;
			}

			$sb = new SetupBlock("General");
			$sb->add_label("Site title: ");
			$sb->add_text_option("title");
			$sb->add_label("<br>Front page: ");
			$sb->add_text_option("front_page");
			$sb->add_label("<br>Base URL: ");
			$sb->add_text_option("base_href");
			$sb->add_label("<br>Data URL: ");
			$sb->add_text_option("data_href");
			$sb->add_label("<br>Contact URL: ");
			$sb->add_text_option("contact_link");
			$sb->add_label("<br>Theme: ");
			$sb->add_choice_option("theme", $themes);
			// $sb->add_label("<br>Anonymous ID: ");
			// $sb->add_int_option("anon_id", 0, 100000);
			$sb->add_hidden_option("anon_id");
			$event->panel->add_main_block($sb, 0);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_string_from_post("title");
			$event->config->set_string_from_post("front_page");
			$event->config->set_string_from_post("base_href");
			$event->config->set_string_from_post("data_href");
			$event->config->set_string_from_post("contact_link");
			$event->config->set_string_from_post("theme");
			$event->config->set_int_from_post("anon_id");
		}
	}
// }}}
// HTML building {{{
	private function build_page($panel) {
		$setupblock_html1 = "";
		$setupblock_html2 = "";

		ksort($panel->mainblocks);

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
		foreach($panel->mainblocks as $block) {
			if(is_a($block, 'SetupBlock')) {
				$html = $this->sb_to_html($block);
				$len = count(explode("<br>", $html));
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

		global $page;
		$page->set_title("Shimmie Setup");
		$page->set_heading("Shimmie Setup");
		$page->add_side_block(new Block("Navigation", $this->build_navigation()), 0);
		$page->add_main_block(new Block("Setup", $table));
	}

	private function build_navigation() {
		return "
			<a href='".make_link("index")."'>Index</a>
			<br><a href='http://trac.shishnet.org/shimmie/wiki/Settings'>Help</a>
		";
	}

	private function sb_to_html($block) {
		return "<div class='setupblock'><b>{$block->header}</b><br>{$block->body}</div>\n";
	}
// }}}
}
add_event_listener(new Setup());
?>
