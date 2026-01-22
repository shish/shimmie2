<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sent when the admin page is ready to be added to
 */
final class AdminBuildingEvent extends Event
{
}

final class AdminActionEvent extends Event
{
    public bool $redirect = true;

    public function __construct(
        public string $action,
        public QueryArray $params
    ) {
        parent::__construct();
    }
}

/** @extends Extension<AdminPageTheme> */
final class AdminPage extends Extension
{
    public const KEY = "admin";

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("admin", method: "GET", permission: AdminPermission::MANAGE_ADMINTOOLS)) {
            send_event(new AdminBuildingEvent());
        }
        if ($event->page_matches("admin/{action}", method: "POST", permission: AdminPermission::MANAGE_ADMINTOOLS)) {
            $action = $event->get_arg('action');
            $aae = new AdminActionEvent($action, $event->POST);

            Log::info("admin", "Util: $action");
            Ctx::$event_bus->set_timeout(null);
            Ctx::$database->set_timeout(null);
            send_event($aae);

            if ($aae->redirect) {
                Ctx::$page->set_redirect(make_link("admin"));
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
                $query = $input->getArgument('query');
                $query = ltrim($query, '/');
                $args = $input->getArgument('args');
                $_SERVER['REQUEST_METHOD'] = 'GET';
                $_SERVER['REQUEST_URI'] = (string)make_link($query);
                if (!is_null($args)) {
                    parse_str($args, $_GET);
                    $_SERVER['REQUEST_URI'] .= "?" . $args;
                }
                send_event(new PageRequestEvent("GET", $query, new QueryArray($_GET), new QueryArray([])));
                Ctx::$page->display();
                return Command::SUCCESS;
            });
        $event->app->register('page:post')
            ->addArgument('query', InputArgument::REQUIRED)
            ->addArgument('args', InputArgument::OPTIONAL)
            ->setDescription('Post a page, eg ip_ban/delete id=1')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $query = $input->getArgument('query');
                $query = ltrim($query, '/');
                $args = $input->getArgument('args');
                if (!is_null($args)) {
                    parse_str($args, $_POST);
                }
                $_SERVER['REQUEST_METHOD'] = 'GET';
                $_SERVER['REQUEST_URI'] = (string)make_link($query);
                send_event(new PageRequestEvent("POST", $query, new QueryArray([]), new QueryArray($_POST)));
                Ctx::$page->display();
                return Command::SUCCESS;
            });
        $event->app->register('get-token')
            ->setDescription('Get a CSRF token')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $output->writeln(Ctx::$user->get_auth_token());
                return Command::SUCCESS;
            });
        $event->app->register('cache:get')
            ->addArgument('key', InputArgument::REQUIRED)
            ->setDescription("Get a cache value")
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $key = $input->getArgument('key');
                $output->writeln(var_export(Ctx::$cache->get($key), true));
                return Command::SUCCESS;
            });
        $event->app->register('cache:set')
            ->addArgument('key', InputArgument::REQUIRED)
            ->addArgument('value', InputArgument::REQUIRED)
            ->setDescription("Set a cache value")
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $key = $input->getArgument('key');
                $value = $input->getArgument('value');
                Ctx::$cache->set($key, $value, 60);
                return Command::SUCCESS;
            });
        $event->app->register('cache:delete')
            ->addArgument('key', InputArgument::REQUIRED)
            ->setDescription("Delete a cache value")
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $key = $input->getArgument('key');
                Ctx::$cache->delete($key);
                return Command::SUCCESS;
            });
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        if ($event->action === "test") {
            Ctx::$page->set_data(MimeType::TEXT, "test");
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_page();
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(AdminPermission::MANAGE_ADMINTOOLS)) {
                $event->add_nav_link(make_link('admin'), "Board Admin", "board_admin");
            }
        }
    }
}
