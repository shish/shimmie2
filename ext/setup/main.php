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
class SetupPanel {
	var $blocks = array();

	public function add_block($block) {
		$this->blocks[] = $block;
	}
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
		$this->section = "main";
		$this->position = 50;
	}

	public function add_label($text) {
		$this->body .= $text;
	}

	public function add_text_option($name, $label=null) {
		global $config;
		$val = $config->get_string($name);
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<input type='text' id='$name' name='_config_$name' value='$val'>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='string'>\n";
	}

	public function add_longtext_option($name, $label=null) {
		global $config;
		$val = $config->get_string($name);
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<textarea rows='5' cols='40' id='$name' name='_config_$name'>$val</textarea>\n";
		$this->body .= "<!--<br><br><br><br>-->\n"; // setup page auto-layout counts <br> tags
		$this->body .= "<input type='hidden' name='_type_$name' value='string'>\n";
	}

	public function add_bool_option($name, $label=null) {
		global $config;
		$checked = $config->get_bool($name) ? " checked" : "";
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<input type='checkbox' id='$name' name='_config_$name'$checked>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='bool'>\n";
	}

//	public function add_hidden_option($name, $label=null) {
//		global $config;
//		$val = $config->get_string($name);
//		$this->body .= "<input type='hidden' id='$name' name='$name' value='$val'>";
//	}

	public function add_int_option($name, $label=null) {
		global $config;
		$val = $config->get_string($name);
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<input type='text' id='$name' name='_config_$name' value='$val' size='4' style='text-align: center;'>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='int'>\n";
	}

	public function add_shorthand_int_option($name, $label=null) {
		global $config;
		$val = to_shorthand_int($config->get_string($name));
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<input type='text' id='$name' name='_config_$name' value='$val' size='6' style='text-align: center;'>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='int'>\n";
	}

	public function add_choice_option($name, $options, $label=null) {
		global $config;
		$current = $config->get_string($name);
		
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$html = "<select id='$name' name='_config_$name'>";
		foreach($options as $optname => $optval) {
			if($optval == $current) $selected=" selected";
			else $selected="";
			$html .= "<option value='$optval'$selected>$optname</option>\n";
		}
		$html .= "</select>";
		$this->body .= "<input type='hidden' name='_type_$name' value='string'>\n";

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
				$page->add_block(new NavBlock());
				$page->add_block(new Block("Permission Denied", "This page is for admins only"));
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
			$sb->position = 0;
			$sb->add_text_option("title", "Site title: ");
			$sb->add_text_option("front_page", "<br>Front page: ");
			$sb->add_text_option("base_href", "<br>Base URL: ");
			$sb->add_text_option("data_href", "<br>Data URL: ");
			$sb->add_text_option("contact_link", "<br>Contact URL:");
			$sb->add_choice_option("theme", $themes, "<br>Theme: ");
			// $sb->add_int_option("anon_id", "<br>Anonymous ID: "); // FIXME: create advanced options page
			$event->panel->add_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			foreach($_POST as $name => $value) {
				if(substr($name, 0, 8) == "_config_") {
					$name = substr($name, 8);
					switch($_POST["_type_$name"]) {
						case "string": $event->config->set_string($name, $value); break;
						case "int":    $event->config->set_int($name, $value);    break;
						case "bool":   $event->config->set_bool($name, $value);   break;
					}
				}
			}
		}

		if(is_a($event, 'UserBlockBuildingEvent')) {
			if($event->user->is_admin()) {
				$event->add_link("Board Config", make_link("setup"));
			}
		}
	}
// }}}
// HTML building {{{
	private function build_page($panel) {
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
		$page->add_block(new Block("Navigation", $this->build_navigation(), "left", 0));
		$page->add_block(new Block("Setup", $table));
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
