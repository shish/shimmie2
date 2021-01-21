<?php declare(strict_types=1);

include_once "config.php";

/*
 * Sent when the setup screen's 'set' button has been
 * activated; new config options are in $_POST
 */
class ConfigSaveEvent extends Event
{
    /** @var Config */
    public $config;

    public function __construct(Config $config)
    {
        parent::__construct();
        $this->config = $config;
    }
}

/*
 * Sent when the setup page is ready to be added to
 */
class SetupBuildingEvent extends Event
{
    /** @var SetupTheme */
    protected $theme;

    /** @var SetupPanel */
    public $panel;

    public function __construct(SetupPanel $panel)
    {
        parent::__construct();
        $this->panel = $panel;
    }
}

class SetupPanel
{
    /** @var SetupBlock[]  */
    public $blocks = [];
    /** @var BaseConfig  */
    public $config;

    public function __construct(BaseConfig $config)
    {
        $this->config = $config;
    }

    public function create_new_block(string $title): SetupBlock
    {
        $block = new SetupBlock($title, $this->config);
        $this->blocks[] = $block;
        return $block;
    }
}

class SetupBlock extends Block
{
    /** @var string  */
    public $header;
    /** @var string  */
    public $body;
    /** @var BaseConfig  */
    public $config;

    public function __construct(string $title, BaseConfig $config)
    {
        parent::__construct($title, "", "main", 50);
        $this->config = $config;
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
        $this->body .= "<tr>";
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
    public function start_table_header_cell(int $colspan = 1, string $align = 'right')
    {
        $this->body .= "<th colspan='$colspan' style='text-align: $align'>";
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

    private function format_option(
        string $name,
        $html,
        ?string $label,
        bool $table_row,
        bool $label_row = false
    ) {
        if ($table_row) {
            $this->start_table_row();
        }
        if ($table_row) {
            $this->start_table_header_cell($label_row ? 2 : 1, $label_row ? 'center' : 'right');
        }
        if (!is_null($label)) {
            $this->body .= "<label for='{$name}'>{$label}</label>";
        }

        if ($table_row) {
            $this->end_table_header_cell();
        }

        if ($table_row && $label_row) {
            $this->end_table_row();
            $this->start_table_row();
        }

        if ($table_row) {
            $this->start_table_cell($label_row ? 2 : 1);
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
        $val = html_escape($this->config->get_string($name));

        $html = "<input type='text' id='{$name}' name='_config_{$name}' value='{$val}'>\n";
        $html .= "<input type='hidden' name='_type_{$name}' value='string'>\n";

        $this->format_option($name, $html, $label, $table_row);
    }

    public function add_longtext_option(string $name, string $label=null, bool $table_row = false)
    {
        $val = html_escape($this->config->get_string($name));

        $rows = max(3, min(10, count(explode("\n", $val))));
        $html = "<textarea rows='$rows' id='$name' name='_config_$name'>$val</textarea>\n";
        $html .= "<input type='hidden' name='_type_$name' value='string'>\n";

        $this->format_option($name, $html, $label, $table_row, true);
    }

    public function add_bool_option(string $name, string $label=null, bool $table_row = false)
    {
        $checked = $this->config->get_bool($name) ? " checked" : "";

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
        $val = $this->config->get_int($name);

        $html = "<input type='number' id='$name' name='_config_$name' value='$val' size='4' style='text-align: center;' step='1' />\n";
        $html .= "<input type='hidden' name='_type_$name' value='int' />\n";

        $this->format_option($name, $html, $label, $table_row);
    }

    public function add_shorthand_int_option(string $name, string $label=null, bool $table_row = false)
    {
        $val = to_shorthand_int($this->config->get_int($name));
        $html = "<input type='text' id='$name' name='_config_$name' value='$val' size='6' style='text-align: center;'>\n";
        $html .= "<input type='hidden' name='_type_$name' value='int'>\n";

        $this->format_option($name, $html, $label, $table_row);
    }

    public function add_choice_option(string $name, array $options, string $label=null, bool $table_row = false)
    {
        if (is_int(array_values($options)[0])) {
            $current = $this->config->get_int($name);
        } else {
            $current = $this->config->get_string($name);
        }

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
        $current = $this->config->get_array($name);

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

    public function add_color_option(string $name, string $label=null, bool $table_row = false)
    {
        $val = html_escape($this->config->get_string($name));

        $html = "<input type='color' id='{$name}' name='_config_{$name}' value='{$val}'>\n";
        $html .= "<input type='hidden' name='_type_{$name}' value='string'>\n";

        $this->format_option($name, $html, $label, $table_row);
    }
}

class Setup extends Extension
{
    /** @var SetupTheme */
    protected $theme;

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
                if ($event->count_args() == 0) {
                    $panel = new SetupPanel($config);
                    send_event(new SetupBuildingEvent($panel));
                    $this->theme->display_page($page, $panel);
                } elseif ($event->get_arg(0) == "save" && $user->check_auth_token()) {
                    send_event(new ConfigSaveEvent($config));
                    $config->save();
                    $page->flash("Config saved");
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("setup"));
                } elseif ($event->get_arg(0) == "advanced") {
                    $this->theme->display_advanced($page, $config->values);
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

        $test_url = str_replace("/index.php", "/nicetest", $_SERVER["SCRIPT_NAME"]);

        $nicescript = "<script type='text/javascript'>
			checkbox = document.getElementById('nice_urls');
			out_span = document.getElementById('nicetest');

			checkbox.disabled = true;
			out_span.innerHTML = '(testing...)';

			document.addEventListener('DOMContentLoaded', () => {
				var http_request = new XMLHttpRequest();
				http_request.open('GET', '$test_url', false);
				http_request.send(null);

				if(http_request.status === 200 && http_request.responseText === 'ok') {
					checkbox.disabled = false;
					out_span.innerHTML = '(tested ok)';
				}
				else {
					checkbox.disabled = true;
					out_span.innerHTML = '(test failed)';
				}
			});
		</script>";
        $sb = $event->panel->create_new_block("General");
        $sb->position = 0;
        $sb->add_text_option(SetupConfig::TITLE, "Site title: ");
        $sb->add_text_option(SetupConfig::FRONT_PAGE, "<br>Front page: ");
        $sb->add_text_option(SetupConfig::MAIN_PAGE, "<br>Main page: ");
        $sb->add_text_option("contact_link", "<br>Contact URL: ");
        $sb->add_choice_option(SetupConfig::THEME, $themes, "<br>Theme: ");
        //$sb->add_multichoice_option("testarray", array("a" => "b", "c" => "d"), "<br>Test Array: ");
        $sb->add_bool_option("nice_urls", "<br>Nice URLs: ");
        $sb->add_label("<span title='$test_url' id='nicetest'>(Javascript inactive, can't test!)</span>$nicescript");

        $sb = $event->panel->create_new_block("Remote API Integration");
        $sb->add_label("<a href='https://akismet.com/'>Akismet</a>");
        $sb->add_text_option("comment_wordpress_key", "<br>API key: ");
        $sb->add_label("<br>&nbsp;<br><a href='https://www.google.com/recaptcha/admin'>ReCAPTCHA</a>");
        $sb->add_text_option("api_recaptcha_privkey", "<br>Secret key: ");
        $sb->add_text_option("api_recaptcha_pubkey", "<br>Site key: ");
    }

    public function onConfigSave(ConfigSaveEvent $event)
    {
        $config = $event->config;
        foreach ($_POST as $_name => $junk) {
            if (substr($_name, 0, 6) == "_type_") {
                $name = substr($_name, 6);
                $type = $_POST["_type_$name"];
                $value = isset($_POST["_config_$name"]) ? $_POST["_config_$name"] : null;
                switch ($type) {
                    case "string": $config->set_string($name, $value); break;
                    case "int":    $config->set_int($name, parse_shorthand_int((string)$value));    break;
                    case "bool":   $config->set_bool($name, bool_escape($value));   break;
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

    public function onCommand(CommandEvent $event)
    {
        if ($event->cmd == "help") {
            print "\tconfig [get|set] <args>\n";
            print "\t\teg 'config get db_version'\n\n";
        }
        if ($event->cmd == "config") {
            global $cache, $config;
            $cmd = $event->args[0];
            $key = $event->args[1];
            switch ($cmd) {
                case "get":
                    print($config->get_string($key) . "\n");
                    break;
                case "set":
                    $config->set_string($key, $event->args[2]);
                    break;
            }
            $cache->delete("config");
        }
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

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event)
    {
        global $config;
        $event->replace('$base', $config->get_string('base_href'));
        $event->replace('$title', $config->get_string(SetupConfig::TITLE));
    }
}
