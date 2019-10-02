<?php

include_once "config.php";

/* ConfigSaveEvent {{{
 *
 * Sent when the setup screen's 'set' button has been
 * activated; new config options are in $_POST
 */
class ConfigSaveEvent extends Event
{
    /** @var Config */
    public $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }
}
// }}}
/* SetupBuildingEvent {{{
 *
 * Sent when the setup page is ready to be added to
 */
class SetupBuildingEvent extends Event
{
    /** @var SetupPanel */
    public $panel;

    public function __construct(SetupPanel $panel)
    {
        $this->panel = $panel;
    }
}
// }}}
/* SetupPanel {{{
 *
 */
class SetupPanel
{
    /** @var SetupBlock[]  */
    public $blocks = [];

    public function add_block(SetupBlock $block)
    {
        $this->blocks[] = $block;
    }
}
// }}}
/* SetupBlock {{{
 *
 */
class SetupBlock extends Block
{
    /** @var string  */
    public $header;
    /** @var string  */
    public $body;



    public function __construct(string $title)
    {
        $this->header = $title;
        $this->section = "main";
        $this->position = 50;
        $this->body = "";
    }

    public function add_label(string $text)
    {
        $this->body .= $text;
    }

    public function start_table()
    {
        $this->body .= "<table class='form'>";
    }
    public function end_table()
    {
        $this->body .= "</table>";
    }
    public function start_table_row()
    {
        $this->body .= "</tr>";
    }
    public function end_table_row()
    {
        $this->body .= "</tr>";
    }
    public function start_table_head()
    {
        $this->body .= "<thead>";
    }
    public function end_table_head()
    {
        $this->body .= "</thead>";
    }
    public function add_table_header($content, int $colspan = 2)
    {
        $this->start_table_head();
        $this->start_table_row();
        $this->add_table_header_cell($content, $colspan);
        $this->end_table_row();
        $this->end_table_head();
    }

    public function start_table_cell(int $colspan = 1)
    {
        $this->body .= "<td colspan='$colspan'>";
    }
    public function end_table_cell()
    {
        $this->body .= "</td>";
    }
    public function add_table_cell($content, int $colspan = 1)
    {
        $this->start_table_cell($colspan);
        $this->body .= $content;
        $this->end_table_cell();
    }
    public function start_table_header_cell(int $colspan = 1)
    {
        $this->body .= "<th colspan='$colspan'>";
    }
    public function end_table_header_cell()
    {
        $this->body .= "</th>";
    }
    public function add_table_header_cell($content, int $colspan = 1)
    {
        $this->start_table_header_cell($colspan);
        $this->body .= $content;
        $this->end_table_header_cell();
    }



    private function format_option(string $name, $html, ?string $label, bool $table_row)
    {
        if ($table_row) {
            $this->start_table_row();
        }
        if ($table_row) {
            $this->start_table_header_cell();
        }
        if (!is_null($label)) {
            $this->body .= "<label for='{$name}'>{$label}</label>";
        }
        if ($table_row) {
            $this->end_table_header_cell();
        }

        if ($table_row) {
            $this->start_table_cell();
        }
        $this->body .= $html;
        if ($table_row) {
            $this->end_table_cell();
        }
        if ($table_row) {
            $this->end_table_row();
        }
    }

    public function add_text_option(string $name, string $label=null, bool $table_row = false)
    {
        global $config;
        $val = html_escape($config->get_string($name));

        $html = "<input type='text' id='{$name}' name='_config_{$name}' value='{$val}'>\n";
        $html .= "<input type='hidden' name='_type_{$name}' value='string'>\n";

        $this->format_option($name, $html, $label, $table_row);
    }

    public function add_longtext_option(string $name, string $label=null, bool $table_row = false)
    {
        global $config;
        $val = html_escape($config->get_string($name));

        $rows = max(3, min(10, count(explode("\n", $val))));
        $html = "<textarea rows='$rows' id='$name' name='_config_$name'>$val</textarea>\n";
        $html .= "<input type='hidden' name='_type_$name' value='string'>\n";

        $this->format_option($name, $html, $label, $table_row);
    }

    public function add_bool_option(string $name, string $label=null, bool $table_row = false)
    {
        global $config;
        $checked = $config->get_bool($name) ? " checked" : "";

        $html = "";
        if (!$table_row&&!is_null($label)) {
            $html .= "<label for='{$name}'>{$label}</label>";
        }

        $html .= "<input type='checkbox' id='$name' name='_config_$name'$checked>\n";
        if ($table_row && !is_null($label)) {
            $html .= "<label for='{$name}'>{$label}</label>";
        }

        $html .= "<input type='hidden' name='_type_$name' value='bool'>\n";

        $this->format_option($name, $html, null, $table_row);
    }

    //	public function add_hidden_option($name, $label=null) {
    //		global $config;
    //		$val = $config->get_string($name);
    //		$this->body .= "<input type='hidden' id='$name' name='$name' value='$val'>";
    //	}

    public function add_int_option(string $name, string $label=null, bool $table_row = false)
    {
        global $config;
        $val = html_escape($config->get_string($name));

        $html = "<input type='text' id='$name' name='_config_$name' value='$val' size='4' style='text-align: center;'>\n";
        $html .= "<input type='hidden' name='_type_$name' value='int'>\n";

        $this->format_option($name, $html, $label, $table_row);
    }

    public function add_shorthand_int_option(string $name, string $label=null, bool $table_row = false)
    {
        global $config;
        $val = to_shorthand_int($config->get_string($name));
        $html = "<input type='text' id='$name' name='_config_$name' value='$val' size='6' style='text-align: center;'>\n";
        $html .= "<input type='hidden' name='_type_$name' value='int'>\n";

        $this->format_option($name, $html, $label, $table_row);
    }

    public function add_choice_option(string $name, array $options, string $label=null, bool $table_row = false)
    {
        global $config;
        $current = $config->get_string($name);

        $html = "<select id='$name' name='_config_$name'>";
        foreach ($options as $optname => $optval) {
            if ($optval == $current) {
                $selected=" selected";
            } else {
                $selected="";
            }
            $html .= "<option value='$optval'$selected>$optname</option>\n";
        }
        $html .= "</select>";
        $html .= "<input type='hidden' name='_type_$name' value='string'>\n";

        $this->format_option($name, $html, $label, $table_row);
    }

    public function add_multichoice_option(string $name, array $options, string $label=null, bool $table_row = false)
    {
        global $config;
        $current = $config->get_array($name);

        $html = "<select id='$name' name='_config_{$name}[]' multiple size='5'>";
        foreach ($options as $optname => $optval) {
            if (in_array($optval, $current)) {
                $selected=" selected";
            } else {
                $selected="";
            }
            $html .= "<option value='$optval'$selected>$optname</option>\n";
        }
        $html .= "</select>";
        $html .= "<input type='hidden' name='_type_$name' value='array'>\n";
        $html .= "<!--<br><br><br><br>-->\n"; // setup page auto-layout counts <br> tags

        $this->format_option($name, $html, $label, $table_row);
    }
}
// }}}

class Setup extends Extension
{
    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_string(SetupConfig::TITLE, "Shimmie");
        $config->set_default_string(SetupConfig::FRONT_PAGE, "post/list");
        $config->set_default_string(SetupConfig::MAIN_PAGE, "post/list");
        $config->set_default_string(SetupConfig::THEME, "default");
        $config->set_default_bool(SetupConfig::WORD_WRAP, true);
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $config, $page, $user;

        if ($event->page_matches("nicetest")) {
            $page->set_mode(PageMode::DATA);
            $page->set_data("ok");
        }

        if ($event->page_matches("setup")) {
            if (!$user->can(Permissions::CHANGE_SETTING)) {
                $this->theme->display_permission_denied();
            } else {
                if ($event->get_arg(0) == "save" && $user->check_auth_token()) {
                    send_event(new ConfigSaveEvent($config));
                    $config->save();
                    flash_message("Config saved");

                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("setup"));
                } elseif ($event->get_arg(0) == "advanced") {
                    $this->theme->display_advanced($page, $config->values);
                } else {
                    $panel = new SetupPanel();
                    send_event(new SetupBuildingEvent($panel));
                    $this->theme->display_page($page, $panel);
                }
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $themes = [];
        foreach (glob("themes/*") as $theme_dirname) {
            $name = str_replace("themes/", "", $theme_dirname);
            $human = str_replace("_", " ", $name);
            $human = ucwords($human);
            $themes[$human] = $name;
        }

        if (isset($_SERVER["HTTP_HOST"])) {
            $host = $_SERVER["HTTP_HOST"];
        } else {
            $host = $_SERVER["SERVER_NAME"];
            if ($_SERVER["SERVER_PORT"] != "80") {
                $host .= ":" . $_SERVER["SERVER_PORT"];
            }
        }
        $full = "//" . $host . $_SERVER["PHP_SELF"];
        $test_url = str_replace("/index.php", "/nicetest", $full);

        $nicescript = "<script type='text/javascript'>
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
				var http_request = getHTTPObject();
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
        $sb->add_text_option(SetupConfig::TITLE, "Site title: ");
        $sb->add_text_option(SetupConfig::FRONT_PAGE, "<br>Front page: ");
        $sb->add_text_option(SetupConfig::MAIN_PAGE, "<br>Main page: ");
        $sb->add_text_option("contact_link", "<br>Contact URL: ");
        $sb->add_choice_option(SetupConfig::THEME, $themes, "<br>Theme: ");
        //$sb->add_multichoice_option("testarray", array("a" => "b", "c" => "d"), "<br>Test Array: ");
        $sb->add_bool_option("nice_urls", "<br>Nice URLs: ");
        $sb->add_label("<span id='nicetest'>(Javascript inactive, can't test!)</span>$nicescript");
        $event->panel->add_block($sb);

        $sb = new SetupBlock("Remote API Integration");
        $sb->add_label("<a href='http://akismet.com/'>Akismet</a>");
        $sb->add_text_option("comment_wordpress_key", "<br>API key: ");
        $sb->add_label("<br>&nbsp;<br><a href='https://www.google.com/recaptcha/admin'>ReCAPTCHA</a>");
        $sb->add_text_option("api_recaptcha_privkey", "<br>Secret key: ");
        $sb->add_text_option("api_recaptcha_pubkey", "<br>Site key: ");
        $event->panel->add_block($sb);
    }

    public function onConfigSave(ConfigSaveEvent $event)
    {
        global $config;
        foreach ($_POST as $_name => $junk) {
            if (substr($_name, 0, 6) == "_type_") {
                $name = substr($_name, 6);
                $type = $_POST["_type_$name"];
                $value = isset($_POST["_config_$name"]) ? $_POST["_config_$name"] : null;
                switch ($type) {
                    case "string": $config->set_string($name, $value); break;
                    case "int":    $config->set_int($name, $value);    break;
                    case "bool":   $config->set_bool($name, $value);   break;
                    case "array":  $config->set_array($name, $value);  break;
                }
            }
        }
        log_warning("setup", "Configuration updated");
        foreach (glob("data/cache/*.css") as $css_cache) {
            unlink($css_cache);
        }
        log_warning("setup", "Cache cleared");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent==="system") {
            if ($user->can(Permissions::CHANGE_SETTING)) {
                $event->add_nav_link("setup", new Link('setup'), "Board Config", null, 0);
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::CHANGE_SETTING)) {
            $event->add_link("Board Config", make_link("setup"));
        }
    }
}
