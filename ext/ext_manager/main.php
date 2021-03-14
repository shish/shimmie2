<?php declare(strict_types=1);


function __extman_extcmp(ExtensionInfo $a, ExtensionInfo $b): int
{
    if ($a->beta===true&&$b->beta===false) {
        return 1;
    }
    if ($a->beta===false&&$b->beta===true) {
        return -1;
    }

    return strcmp($a->name, $b->name);
}

function __extman_extactive(ExtensionInfo $a): bool
{
    return Extension::is_enabled($a->key);
}


class ExtensionAuthor
{
    public string $name;
    public ?string $email;

    public function __construct(string $name, ?string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }
}

class ExtManager extends Extension
{
    /** @var ExtManagerTheme */
    protected ?Themelet $theme;

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;
        if ($event->page_matches("ext_manager")) {
            if ($user->can(Permissions::MANAGE_EXTENSION_LIST)) {
                if ($event->count_args() == 1 && $event->get_arg(0) == "set" && $user->check_auth_token()) {
                    if (is_writable("data/config")) {
                        $this->set_things($_POST);
                        log_warning("ext_manager", "Active extensions changed", "Active extensions changed");
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("ext_manager"));
                    } else {
                        $this->theme->display_error(
                            500,
                            "File Operation Failed",
                            "The config file (data/config/extensions.conf.php) isn't writable by the web server :("
                        );
                    }
                } else {
                    $this->theme->display_table($page, $this->get_extensions(true), true);
                }
            } else {
                $this->theme->display_table($page, $this->get_extensions(false), false);
            }
        }

        if ($event->page_matches("ext_doc")) {
            if ($event->count_args() == 1) {
                $ext = $event->get_arg(0);
                if (file_exists("ext/$ext/info.php")) {
                    $info = ExtensionInfo::get_by_key($ext);
                    $this->theme->display_doc($page, $info);
                }
            } else {
                $this->theme->display_table($page, $this->get_extensions(false), false);
            }
        }
    }

    public function onCommand(CommandEvent $event)
    {
        if ($event->cmd == "help") {
            print "\tdisable-all-ext\n";
            print "\t\tdisable all extensions\n\n";
        }
        if ($event->cmd == "disable-all-ext") {
            $this->write_config([]);
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent==="system") {
            if ($user->can(Permissions::MANAGE_EXTENSION_LIST)) {
                $event->add_nav_link("ext_manager", new Link('ext_manager'), "Extension Manager");
            } else {
                $event->add_nav_link("ext_doc", new Link('ext_doc'), "Board Help");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::MANAGE_EXTENSION_LIST)) {
            $event->add_link("Extension Manager", make_link("ext_manager"));
        }
    }

    /**
     * #return ExtensionInfo[]
     */
    private function get_extensions(bool $all): array
    {
        $extensions = ExtensionInfo::get_all();
        if (!$all) {
            $extensions = array_filter($extensions, "__extman_extactive");
        }
        usort($extensions, "__extman_extcmp");
        return $extensions;
    }

    private function set_things($settings)
    {
        $core = ExtensionInfo::get_core_extensions();
        $extras = [];

        foreach (ExtensionInfo::get_all_keys() as $key) {
            if (!in_array($key, $core) && isset($settings["ext_$key"])) {
                $extras[] = $key;
            }
        }

        $this->write_config($extras);
    }

    /**
     * #param string[] $extras
     */
    private function write_config(array $extras)
    {
        file_put_contents(
            "data/config/extensions.conf.php",
            '<' . '?php' . "\n" .
            'define("EXTRA_EXTS", "' . implode(",", $extras) . '");' . "\n"
        );

        // when the list of active extensions changes, we can be
        // pretty sure that the list of who reacts to what will
        // change too
        _clear_cached_event_listeners();
    }
}
