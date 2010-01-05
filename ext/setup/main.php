<?php
/*
 * Name: Board Config
 * Author: Shish
 * Visibility: admin
 * Description: Allows the site admin to configure the board to his or her taste
 */

/* ConfigSaveEvent {{{
 *
 * Sent when the setup screen's 'set' button has been
 * activated; new config options are in $_POST
 */
class ConfigSaveEvent extends Event {
	var $config;

	public function ConfigSaveEvent($config) {
		$this->config = $config;
	}
}
// }}}
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
		$this->body .= "<textarea rows='5' id='$name' name='_config_$name'>$val</textarea>\n";
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

	public function add_multichoice_option($name, $options, $label=null) {
		global $config;
		$current = $config->get_array($name);

		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$html = "<select id='$name' name='_config_{$name}[]' multiple size='5'>";
		foreach($options as $optname => $optval) {
			if(in_array($optval, $current)) $selected=" selected";
			else $selected="";
			$html .= "<option value='$optval'$selected>$optname</option>\n";
		}
		$html .= "</select>";
		$this->body .= "<input type='hidden' name='_type_$name' value='array'>\n";
		$this->body .= "<!--<br><br><br><br>-->\n"; // setup page auto-layout counts <br> tags

		$this->body .= $html;
	}
}
// }}}

class Setup extends SimpleExtension {
	public function onInitExt($event) {
		global $config;
		$config->set_default_string("title", "Shimmie");
		$config->set_default_string("front_page", "post/list");
		$config->set_default_string("main_page", "post/list");
		$config->set_default_string("base_href", "./index.php?q=");
		$config->set_default_string("theme", "default");
		$config->set_default_bool("use_autodate", true);
		$config->set_default_bool("word_wrap", true);
		$config->set_default_string("use_autodate", "F j, Y");
	}

	public function onPageRequest($event) {
		global $config, $page, $user;

		if($event->page_matches("nicetest")) {
			$page->set_mode("data");
			$page->set_data("ok");
		}

		if($event->page_matches("setup")) {
			if(!$user->is_admin()) {
				$this->theme->display_permission_denied($page);
			}
			else {
				if($event->get_arg(0) == "save") {
					send_event(new ConfigSaveEvent($config));
					$config->save();

					$page->set_mode("redirect");
					$page->set_redirect(make_link("setup"));
				}
				else if($event->get_arg(0) == "advanced") {
					$this->theme->display_advanced($page, $config->values);
				}
				else {
					$panel = new SetupPanel();
					send_event(new SetupBuildingEvent($panel));
					$this->theme->display_page($page, $panel);
				}
			}
		}
	}

	public function onSetupBuilding($event) {
		$themes = array();
		foreach(glob("themes/*") as $theme_dirname) {
			$name = str_replace("themes/", "", $theme_dirname);
			$human = str_replace("_", " ", $name);
			$human = ucwords($human);
			$themes[$human] = $name;
		}

		$full = "http://" . $_SERVER["SERVER_NAME"] . $_SERVER["PHP_SELF"];
		$test_url = str_replace("/index.php", "/nicetest", $full);

		$nicescript = "<script language='javascript'>
			function getHTTPObject() {
				if (window.XMLHttpRequest){
					return new XMLHttpRequest();
				}
				else if(window.ActiveXObject){
					return new ActiveXObject('Microsoft.XMLHTTP');
				}
			}

			checkbox = document.getElementById('nice_urls');
			out_span = document.getElementById('nicetest');

			checkbox.disabled = true;
			out_span.innerHTML = '(testing...)';

			$(document).ready(function() {
				http_request = getHTTPObject();
				http_request.open('GET', '$test_url', false);
				http_request.send(null);

				if(http_request.status == 200 && http_request.responseText == 'ok') {
					checkbox.disabled = false;
					out_span.innerHTML = '(tested ok)';
				}
				else {
					checkbox.disabled = true;
					out_span.innerHTML = '(test failed)';
				}
			});
		</script>";
		$sb = new SetupBlock("General");
		$sb->position = 0;
		$sb->add_text_option("title", "Site title: ");
		$sb->add_text_option("front_page", "<br>Front page: ");
		$sb->add_text_option("main_page", "<br>Main page: ");
		$sb->add_text_option("contact_link", "<br>Contact URL: ");
		$sb->add_choice_option("theme", $themes, "<br>Theme: ");
		//$sb->add_multichoice_option("testarray", array("a" => "b", "c" => "d"), "<br>Test Array: ");
		$sb->add_bool_option("nice_urls", "<br>Nice URLs: ");
		$sb->add_label("<span id='nicetest'>(Javascript inactive, can't test!)</span>$nicescript");
		$event->panel->add_block($sb);

		$sb = new SetupBlock("Remote API Integration");
		$sb->add_label("<a href='http://akismet.com/'>Akismet</a>");
		$sb->add_text_option("comment_wordpress_key", "<br>API key: ");
		$sb->add_label(
			"<br>&nbsp;<br><a href='".
			recaptcha_get_signup_url($_SERVER["HTTP_HOST"], "Shimmie").
			"'>ReCAPTCHA</a>");
		$sb->add_text_option("api_recaptcha_privkey", "<br>Private key: ");
		$sb->add_text_option("api_recaptcha_pubkey", "<br>Public key: ");
		$event->panel->add_block($sb);
	}

	public function onConfigSave($event) {
		global $config;
		foreach($_POST as $_name => $junk) {
			if(substr($_name, 0, 6) == "_type_") {
				$name = substr($_name, 6);
				$type = $_POST["_type_$name"];
				$value = isset($_POST["_config_$name"]) ? $_POST["_config_$name"] : null;
				switch($type) {
					case "string": $config->set_string($name, $value); break;
					case "int":    $config->set_int($name, $value);    break;
					case "bool":   $config->set_bool($name, $value);   break;
					case "array":  $config->set_array($name, $value);  break;
				}
			}
		}
		log_warning("setup", "Configuration updated");
	}

	public function onUserBlockBuilding($event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("Board Config", make_link("setup"));
		}
	}
}
?>
