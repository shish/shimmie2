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

final class AdminPage extends Extension
{
    public const KEY = "admin";
    /** @var AdminPageTheme */
    protected Themelet $theme;

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
        $event->app->register('site:backup')
            ->setDescription('Write data / metadata / configuration into a .zip file')
            ->addArgument('output', InputArgument::REQUIRED)
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $output = $input->getArgument('output');

                // dump database to folder
                $db_engine = Ctx::$database->get_driver_id();
                $dump_cmd = match($db_engine) {
                    DatabaseDriverID::MYSQL => "mysqldump -u root -p shimmie2",
                    DatabaseDriverID::PGSQL => "pg_dump -U postgres shimmie2",
                    DatabaseDriverID::SQLITE => "sqlite3 shimmie2.db .dump",
                };
                $dump_file = shm_tempnam("backup-sql");
                // FIXME: run $dump_cmd with output into $dump_file
                $dump_file->put_contents("TODO: sql dump");

                // create archive
                $zip = new \ZipArchive();
                $zip->open($output, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                // special files
                $zip->addFile($dump_file->str(), "database.sql");
                $zip->addFromString("backup.json", \Safe\json_encode([
                    "db_engine" => $db_engine->value,
                ]));
                // FIXME: add everything else in data/ (minus data/cache and data/shimmie.*.sqlite?)
                foreach (Filesystem::get_files_recursively(new Path("data")) as $path) {
                    $internal = $path->relative_to(new Path("data"))->str();
                    if (
                        str_contains($internal, "data/temp/")
                        || str_contains($internal, "data/phpunit.cache/")
                        || str_contains($internal, "data/coverage/")
                        || str_contains($internal, "data/cache/")
                        || fnmatch("data/*.sqlite", $internal)
                    ) {
                        continue;
                    }
                    $zip->addFile($path->str(), $internal);
                }
                $zip->close();

                return Command::SUCCESS;
            });
        $event->app->register('site:restore')
            ->setDescription('Read data / metadata / configuration from a .zip file')
            ->addArgument('output', InputArgument::REQUIRED)
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                // if data/ is empty, then Installer.php should intercept this command
                // and do the restoration - if we're here, it means that we are already
                // in a live instance
                $output->writeln("Backups can only be restored into an empty instance (no database, no data/ folder)");
                return Command::FAILURE;
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
        $event->app->register('cache:del')
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
                $event->add_nav_link(make_link('admin'), "Board Admin");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(AdminPermission::MANAGE_ADMINTOOLS)) {
            $event->add_link("Board Admin", make_link("admin"));
        }
    }
}
