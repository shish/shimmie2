<?php declare(strict_types=1);

/**
 * Sent when the admin page is ready to be added to
 */
class AdminBuildingEvent extends Event
{
    public Page $page;

    public function __construct(Page $page)
    {
        parent::__construct();
        $this->page = $page;
    }
}

class AdminActionEvent extends Event
{
    public string $action;
    public bool $redirect = true;

    public function __construct(string $action)
    {
        parent::__construct();
        $this->action = $action;
    }
}

class AdminPage extends Extension
{
    /** @var AdminPageTheme */
    protected ?Themelet $theme;

    public function onPageRequest(PageRequestEvent $event)
    {
        global $database, $page, $user;

        if ($event->page_matches("admin")) {
            if (!$user->can(Permissions::MANAGE_ADMINTOOLS)) {
                $this->theme->display_permission_denied();
            } else {
                if ($event->count_args() == 0) {
                    send_event(new AdminBuildingEvent($page));
                } else {
                    $action = $event->get_arg(0);
                    $aae = new AdminActionEvent($action);

                    if ($user->check_auth_token()) {
                        log_info("admin", "Util: $action");
                        set_time_limit(0);
                        $database->set_timeout(300000);
                        send_event($aae);
                    }

                    if ($aae->redirect) {
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("admin"));
                    }
                }
            }
        }
    }

    public function onCommand(CommandEvent $event)
    {
        if ($event->cmd == "help") {
            print "\tget-page <query string>\n";
            print "\t\teg 'get-page post/list'\n\n";
            print "\tpost-page <query string> <urlencoded params>\n";
            print "\t\teg 'post-page ip_ban/delete id=1'\n\n";
            print "\tget-token\n";
            print "\t\tget a CSRF auth token\n\n";
            print "\tregen-thumb <id / hash>\n";
            print "\t\tregenerate a thumbnail\n\n";
            print "\tcache [get|set|del] [key] <value>\n";
            print "\t\teg 'cache get config'\n\n";
        }
        if ($event->cmd == "get-page") {
            global $page;
            $_SERVER['REQUEST_URI'] = $event->args[0];
            if (isset($event->args[1])) {
                parse_str($event->args[1], $_GET);
                $_SERVER['REQUEST_URI'] .= "?" . $event->args[1];
            }
            send_event(new PageRequestEvent($event->args[0]));
            $page->display();
        }
        if ($event->cmd == "post-page") {
            global $page;
            $_SERVER['REQUEST_METHOD'] = "POST";
            if (isset($event->args[1])) {
                parse_str($event->args[1], $_POST);
            }
            send_event(new PageRequestEvent($event->args[0]));
            $page->display();
        }
        if ($event->cmd == "get-token") {
            global $user;
            print($user->get_auth_token());
        }
        if ($event->cmd == "regen-thumb") {
            $uid = $event->args[0];
            $image = Image::by_id_or_hash($uid);
            if ($image) {
                send_event(new ThumbnailGenerationEvent($image->hash, $image->get_mime(), true));
            } else {
                print("No post with ID '$uid'\n");
            }
        }
        if ($event->cmd == "cache") {
            global $cache;
            $cmd = $event->args[0];
            $key = $event->args[1];
            switch ($cmd) {
                case "get":
                    var_dump($cache->get($key));
                    break;
                case "set":
                    $cache->set($key, $event->args[2], 60);
                    break;
                case "del":
                    $cache->delete($key);
                    break;
            }
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event)
    {
        $this->theme->display_page();
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent==="system") {
            if ($user->can(Permissions::MANAGE_ADMINTOOLS)) {
                $event->add_nav_link("admin", new Link('admin'), "Board Admin");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::MANAGE_ADMINTOOLS)) {
            $event->add_link("Board Admin", make_link("admin"));
        }
    }
}
