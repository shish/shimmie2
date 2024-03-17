<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

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
    /** @var array<string, mixed> */
    public array $params;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(string $action, array $params)
    {
        parent::__construct();
        $this->action = $action;
        $this->params = $params;
    }
}

class AdminPage extends Extension
{
    /** @var AdminPageTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database, $page, $user;

        if ($event->page_matches("admin", method: "GET", permission: Permissions::MANAGE_ADMINTOOLS)) {
            send_event(new AdminBuildingEvent($page));
        }
        if ($event->page_matches("admin/{action}", method: "POST", permission: Permissions::MANAGE_ADMINTOOLS)) {
            $action = $event->get_arg('action');
            $aae = new AdminActionEvent($action, $event->POST);

            log_info("admin", "Util: $action");
            shm_set_timeout(null);
            $database->set_timeout(null);
            send_event($aae);

            if ($aae->redirect) {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("admin"));
            }
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('page:get')
            ->addArgument('query', InputArgument::REQUIRED)
            ->addArgument('args', InputArgument::OPTIONAL)
            ->setDescription('Get a page, eg /post/list')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $page;
                $query = $input->getArgument('query');
                $args = $input->getArgument('args');
                $_SERVER['REQUEST_URI'] = make_link($query);
                if (!is_null($args)) {
                    parse_str($args, $_GET);
                    $_SERVER['REQUEST_URI'] .= "?" . $args;
                }
                send_event(new PageRequestEvent("GET", $query, $_GET, []));
                $page->display();
                return Command::SUCCESS;
            });
        $event->app->register('page:post')
            ->addArgument('query', InputArgument::REQUIRED)
            ->addArgument('args', InputArgument::OPTIONAL)
            ->setDescription('Post a page, eg ip_ban/delete id=1')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $page;
                $query = $input->getArgument('query');
                $args = $input->getArgument('args');
                global $page;
                if (!is_null($args)) {
                    parse_str($args, $_POST);
                }
                send_event(new PageRequestEvent("POST", $query, [], $_POST));
                $page->display();
                return Command::SUCCESS;
            });
        $event->app->register('get-token')
            ->setDescription('Get a CSRF token')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $user;
                $output->writeln($user->get_auth_token());
                return Command::SUCCESS;
            });
        $event->app->register('cache:get')
            ->addArgument('key', InputArgument::REQUIRED)
            ->setDescription("Get a cache value")
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $cache;
                $key = $input->getArgument('key');
                $output->writeln(var_export($cache->get($key), true));
                return Command::SUCCESS;
            });
        $event->app->register('cache:set')
            ->addArgument('key', InputArgument::REQUIRED)
            ->addArgument('value', InputArgument::REQUIRED)
            ->setDescription("Set a cache value")
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $cache;
                $key = $input->getArgument('key');
                $value = $input->getArgument('value');
                $cache->set($key, $value, 60);
                return Command::SUCCESS;
            });
        $event->app->register('cache:del')
            ->addArgument('key', InputArgument::REQUIRED)
            ->setDescription("Delete a cache value")
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $cache;
                $key = $input->getArgument('key');
                $cache->delete($key);
                return Command::SUCCESS;
            });
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        global $page;
        if ($event->action === "test") {
            $page->set_mode(PageMode::DATA);
            $page->set_data("test");
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_page();
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(Permissions::MANAGE_ADMINTOOLS)) {
                $event->add_nav_link("admin", new Link('admin'), "Board Admin");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::MANAGE_ADMINTOOLS)) {
            $event->add_link("Board Admin", make_link("admin"));
        }
    }
}
