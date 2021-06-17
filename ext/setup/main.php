<?php declare(strict_types=1);

include_once "config.php";

/*
 * Sent when the setup screen's 'set' button has been
 * activated; new config options are in $_POST
 */
class ConfigSaveEvent extends Event
{
    public Config $config;

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
    protected SetupTheme $theme;
    public SetupPanel $panel;

    public function __construct(SetupPanel $panel)
    {
        parent::__construct();
        $this->panel = $panel;
    }
}

class SetupPanel
{
    /** @var SetupBlock[]  */
    public array $blocks = [];
    public BaseConfig $config;
    public SetupTheme $theme;

    public function __construct(BaseConfig $config, SetupTheme $theme)
    {
        $this->config = $config;
        $this->theme = $theme;
    }

    public function create_new_block(string $title): SetupBlock
    {
        $block = new SetupBlock($title, $this->config, $this->theme);
        $this->blocks[] = $block;
        return $block;
    }
}

class SetupBlock extends Block
{
    public ?string $header;
    public ?string $body;
    public BaseConfig $config;
    public SetupTheme $theme;

    public function __construct(string $title, BaseConfig $config, SetupTheme $theme)
    {
        parent::__construct($title, "", "main", 50);
        $this->config = $config;
        $this->theme = $theme;
    }

    public function add_label(string $text, bool $full_width=false)
    {
        $this->body .= $this->theme->format_item($text, null, null, false, $full_width);
    }

    public function add_header(string $text)
    {
        $this->body .= $this->theme->format_item($text, null, null, true);
    }

    protected function add_option(?string $label, ?string $html, ?string $name, bool $label_is_header=false, bool $full_width=false)
    {
        $this->body .= $this->theme->format_item($label, $html, $name, $label_is_header, $full_width);
    }

    public function add_text_option(string $name, string $label=null, string $tooltip="")
    {
        $val = html_escape($this->config->get_string($name));

        $html = "<input type='text' id='{$name}' name='_config_{$name}' value='{$val}'>\n";
        $html .= "<input type='hidden' name='_type_{$name}' value='string'>\n";

        $html .= $tooltip;

        $this->add_option($label, $html, $name, false);
    }

    public function add_longtext_option(string $name, string $label=null)
    {
        $val = html_escape($this->config->get_string($name));

        $rows = max(3, min(10, count(explode("\n", $val))));
        $html = "<textarea rows='$rows' id='$name' name='_config_$name'>$val</textarea>\n";
        $html .= "<input type='hidden' name='_type_$name' value='string'>\n";

        $this->add_option($label, $html, $name, false, true);
    }

    public function add_bool_option(string $name, string $label=null, string $tooltip="")
    {
        $checked = $this->config->get_bool($name) ? " checked" : "";

        $html = "";

        $html .= "<input type='checkbox' id='$name' name='_config_$name'$checked>\n";
        $html .= "<input type='hidden' name='_type_$name' value='bool'>\n";

        $html .= $tooltip;

        $this->add_option($label, $html, $name, false);
    }

    //	public function add_hidden_option($name, $label=null) {
    //		global $config;
    //		$val = $config->get_string($name);
    //		$this->body .= "<input type='hidden' id='$name' name='$name' value='$val'>";
    //	}

    public function add_int_option(string $name, string $label=null, string $tooltip="")
    {
        $val = $this->config->get_int($name);

        $html = "<input type='number' id='$name' name='_config_$name' value='$val' size='4' style='text-align: center;' step='1' />\n";
        $html .= "<input type='hidden' name='_type_$name' value='int' />\n";

        $html .= $tooltip;

        $this->add_option($label, $html, $name, false);
    }

    public function add_shorthand_int_option(string $name, string $label=null, string $tooltip="")
    {
        $val = to_shorthand_int($this->config->get_int($name));
        $html = "<input type='text' id='$name' name='_config_$name' value='$val' size='6' style='text-align: center;'>\n";
        $html .= "<input type='hidden' name='_type_$name' value='int'>\n";

        $html .= $tooltip;

        $this->add_option($label, $html, $name, false);
    }

    public function add_choice_option(string $name, array $options, string $label=null)
    {
        if (is_int(array_values($options)[0])) {
            $current = $this->config->get_int($name);
        } else {
            $current = $this->config->get_string($name);
        }

        // Not on this branch yet. Also requires build_selector to be public since we're accesing it from a block, not a theme.
        // $html = $this->theme->build_selector("_config_".$name, array_flip($options), "id='".$name."'", false, [$current]);

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

        $this->add_option($label, $html, $name, false);
    }

    public function add_multichoice_option(string $name, array $options, string $label=null)
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

        $this->add_option($label, $html, $name, false);
    }

    public function add_color_option(string $name, string $label=null)
    {
        $val = html_escape($this->config->get_string($name));

        $html = "<input type='color' id='{$name}' name='_config_{$name}' value='{$val}'>\n";
        $html .= "<input type='hidden' name='_type_{$name}' value='string'>\n";

        $this->add_option($label, $html, $name, false);
    }
}

class Setup extends Extension
{
    /** @var SetupTheme */
    protected ?Themelet $theme;

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
                    $panel = new SetupPanel($config, $this->theme);
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
        $sb->add_text_option(SetupConfig::FRONT_PAGE, "Front page: ");
        $sb->add_text_option(SetupConfig::MAIN_PAGE, "Main page: ");
        $sb->add_text_option("contact_link", "Contact URL: ");
        $sb->add_choice_option(SetupConfig::THEME, $themes, "Theme: ");
        //$sb->add_multichoice_option("testarray", array("a" => "b", "c" => "d"), "Test Array: ");
        $sb->add_bool_option(
            "nice_urls",
            "Nice URLs: ",
            "<span title='$test_url' id='nicetest'>(Javascript inactive, can't test!)</span>$nicescript"
        );

        $sb = $event->panel->create_new_block("Remote API Integration");
        $sb->add_label("<a href='https://akismet.com/'>Akismet</a>", true);
        $sb->add_text_option("comment_wordpress_key", "API key: ");
        $sb->add_label("&nbsp;<a href='https://www.google.com/recaptcha/admin'>ReCAPTCHA</a>", true);
        $sb->add_text_option("api_recaptcha_privkey", "Secret key: ");
        $sb->add_text_option("api_recaptcha_pubkey", "Site key: ");
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
