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
		$val = html_escape($config->get_string($name));
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<input type='text' id='$name' name='_config_$name' value='$val'>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='string'>\n";
	}

	public function add_longtext_option($name, $label=null) {
		global $config;
		$val = html_escape($config->get_string($name));
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
		$val = html_escape($config->get_string($name));
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
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("setup", "SetupTheme");

		if(is_a($event, 'InitExtEvent')) {
			global $config;
			$config->set_default_string("title", "Shimmie");
			$config->set_default_string("front_page", "post/list");
			$config->set_default_string("main_page", "post/list");
			$config->set_default_string("base_href", "./index.php?q=");
			$config->set_default_string("theme", "default");
		}

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "setup")) {
			global $user;
			if(!$user->is_admin()) {
				$this->theme->display_error($event->page, "Permission Denied", "This page is for admins only");
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
				else if($event->get_arg(0) == "advanced") {
					global $config;
					$this->theme->display_advanced($event->page, $config->values);
				}
				else {
					$panel = new SetupPanel();
					send_event(new SetupBuildingEvent($panel));
					$this->theme->display_page($event->page, $panel);
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
			$sb->add_text_option("main_page", "<br>Main page: ");
			$sb->add_text_option("base_href", "<br>Base URL: ");
			$sb->add_text_option("contact_link", "<br>Contact URL: ");
			$sb->add_choice_option("theme", $themes, "<br>Theme: ");
			$event->panel->add_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			foreach($_POST as $_name => $junk) {
				if(substr($_name, 0, 6) == "_type_") {
					$name = substr($_name, 6);
					$type = $_POST["_type_$name"];
					$value = isset($_POST["_config_$name"]) ? $_POST["_config_$name"] : null;
					switch($type) {
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
}
add_event_listener(new Setup());
?>
