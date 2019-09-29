<?php
/**
 * Name: Extension Manager
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Visibility: admin
 * Description: A thing for point & click extension management
 * Documentation:
 *   Allows the admin to view a list of all extensions and enable or
 *   disable them; also allows users to view the list of activated
 *   extensions and read their documentation
 */

function __extman_extcmp(ExtensionInfo $a, ExtensionInfo $b): int
{
    return strcmp($a->name, $b->name);
}

class ExtensionInfo
{
    public $ext_name;
    public $name;
    public $link;
    public $authors;
    public $description;
    public $documentation;
    public $version;
    public $visibility;
    public $enabled;

    public function __construct($main)
    {
        $matches = [];
        $lines = file($main);
        $number_of_lines = count($lines);
        preg_match("#ext/(.*)/main.php#", $main, $matches);
        $this->ext_name = $matches[1];
        $this->name = $this->ext_name;
        $this->enabled = $this->is_enabled($this->ext_name);
        $this->authors = [];

        for ($i = 0; $i < $number_of_lines; $i++) {
            $line = $lines[$i];
            if (preg_match("/Name: (.*)/", $line, $matches)) {
                $this->name = $matches[1];
            } elseif (preg_match("/Visibility: (.*)/", $line, $matches)) {
                $this->visibility = $matches[1];
            } elseif (preg_match("/Link: (.*)/", $line, $matches)) {
                $this->link = $matches[1];
                if ($this->link[0] == "/") {
                    $this->link = make_link(substr($this->link, 1));
                }
            } elseif (preg_match("/Version: (.*)/", $line, $matches)) {
                $this->version = $matches[1];
            } elseif (preg_match("/Authors?: (.*)/", $line, $matches)) {
                $author_list = explode(',', $matches[1]);
                foreach ($author_list as $author) {
                    if (preg_match("/(.*) [<\(](.*@.*)[>\)]/", $author, $matches)) {
                        $this->authors[] = new ExtensionAuthor($matches[1], $matches[2]);
                    } else {
                        $this->authors[] = new ExtensionAuthor($author, null);
                    }
                }
            } elseif (preg_match("/(.*)Description: ?(.*)/", $line, $matches)) {
                $this->description = $matches[2];
                $start = $matches[1] . " ";
                $start_len = strlen($start);
                while (substr($lines[$i + 1], 0, $start_len) == $start) {
                    $this->description .= " " . substr($lines[$i + 1], $start_len);
                    $i++;
                }
            } elseif (preg_match("/(.*)Documentation: ?(.*)/", $line, $matches)) {
                $this->documentation = $matches[2];
                $start = $matches[1] . " ";
                $start_len = strlen($start);
                while (substr($lines[$i + 1], 0, $start_len) == $start) {
                    $this->documentation .= " " . substr($lines[$i + 1], $start_len);
                    $i++;
                }
                $this->documentation = str_replace('$site', make_http(get_base_href()), $this->documentation);
            } elseif (preg_match("/\*\//", $line, $matches)) {
                break;
            }
        }
    }

    private function is_enabled(string $fname): ?bool
    {
        $core = explode(",", CORE_EXTS);
        $extra = explode(",", EXTRA_EXTS);

        if (in_array($fname, $extra)) {
            return true;
        } // enabled
        if (in_array($fname, $core)) {
            return null;
        } // core
        return false; // not enabled
    }
}

class ExtensionAuthor
{
    public $name;
    public $email;

    public function __construct(string $name, ?string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }
}

class ExtManager extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;
        if ($event->page_matches("ext_manager")) {
            if ($user->can(Permissions::MANAGE_EXTENSION_LIST)) {
                if ($event->get_arg(0) == "set" && $user->check_auth_token()) {
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
            $ext = $event->get_arg(0);
            if (file_exists("ext/$ext/main.php")) {
                $info = new ExtensionInfo("ext/$ext/main.php");
                $this->theme->display_doc($page, $info);
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
        } else {
            $event->add_link("Help", make_link("ext_doc"));
        }
    }

    /**
     * #return ExtensionInfo[]
     */
    private function get_extensions(bool $all): array
    {
        $extensions = [];
        if ($all) {
            $exts = zglob("ext/*/main.php");
        } else {
            $exts = zglob("ext/{" . ENABLED_EXTS . "}/main.php");
        }
        foreach ($exts as $main) {
            $extensions[] = new ExtensionInfo($main);
        }
        usort($extensions, "__extman_extcmp");
        return $extensions;
    }

    private function set_things($settings)
    {
        $core = explode(",", CORE_EXTS);
        $extras = [];

        foreach (glob("ext/*/main.php") as $main) {
            $matches = [];
            preg_match("#ext/(.*)/main.php#", $main, $matches);
            $fname = $matches[1];

            if (!in_array($fname, $core) && isset($settings["ext_$fname"])) {
                $extras[] = $fname;
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
            'define("EXTRA_EXTS", "' . implode(",", $extras) . '");' . "\n" .
            '?' . ">"
        );

        // when the list of active extensions changes, we can be
        // pretty sure that the list of who reacts to what will
        // change too
        _clear_cached_event_listeners();
    }
}
