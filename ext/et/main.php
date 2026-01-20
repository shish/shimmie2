<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/** @extends Extension<ETTheme> */
final class ET extends Extension
{
    public const KEY = "et";

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("system_info", permission: ETPermission::VIEW_SYSINFO)) {
            $this->theme->display_info_page(
                \Safe\preg_replace("/\n([a-z])/", "\n\n\$1", Yaml::dump($this->get_site_info(), 2, 2)),
                Yaml::dump($this->get_system_info(), 2, 2),
            );
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(ETPermission::VIEW_SYSINFO)) {
                $event->add_nav_link(make_link('system_info'), "System Info", "info", order: 10);
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(ETPermission::VIEW_SYSINFO)) {
            $event->add_link("System Info", make_link("system_info"), 99);
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('info')
            ->setDescription('List a bunch of info')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                print(Yaml::dump($this->get_site_info(), 2, 2));
                return Command::SUCCESS;
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function get_site_info(): array
    {
        $config = Ctx::$config;
        $database = Ctx::$database;

        $core_exts = [];
        $extra_exts = [];
        foreach (ExtensionInfo::get_all() as $key => $info) {
            if ($info::is_enabled()) {
                if ($info->core) {
                    $core_exts[] = $info::KEY;
                } else {
                    $extra_exts[] = $info::KEY;
                }
            }
        }

        $disk_total = \Safe\disk_total_space("./");
        $disk_free = \Safe\disk_free_space("./");
        $info = [
            "about" => [
                'title' => $config->get(SetupConfig::TITLE),
                'theme' => $config->get(SetupConfig::THEME),
                'url'   => (string)(make_link("")->asAbsolute()),
            ],
            "versions" => [
                'shimmie' => SysConfig::getVersion(),
                'schema'  => $config->get(Upgrade::VERSION_KEY, ConfigType::INT),
                'php'     => phpversion(),
                'db'      => $database->get_driver_id()->value . " " . $database->get_version(),
                'os'      => php_uname(),
                'server'  =>  $_SERVER["SERVER_SOFTWARE"] ?? 'unknown',
            ],
            "extensions" => [
                "core" => $core_exts,
                "extra" => $extra_exts,
                "handled_mimes" => array_values(array_map(fn ($mime) => (string)$mime, DataHandlerExtension::get_all_supported_mimes())),
            ],
            "stats" => [
                'images'   => (int)$database->get_one("SELECT COUNT(*) FROM images"),
                'comments' => (int)$database->get_one("SELECT COUNT(*) FROM comments"),
                'users'    => (int)$database->get_one("SELECT COUNT(*) FROM users"),
            ],
            "media" => [
                "memory_limit" => to_shorthand_int($config->get(MediaConfig::MEM_LIMIT)),
                "disk_use" => to_shorthand_int($disk_total - $disk_free),
                "disk_total" => to_shorthand_int($disk_total),
            ],
            "thumbnails" => [
                "engine" => $config->get(ThumbnailConfig::ENGINE),
                "mime" => $config->get(ThumbnailConfig::MIME),
            ],
        ];

        if (file_exists(".git")) {
            try {
                $commitHash = trim(\Safe\exec('git log --pretty="%h" -n1 HEAD', result_code: $r1));
                $commitBranch = trim(\Safe\exec('git rev-parse --abbrev-ref HEAD', result_code: $r2));
                $commitOrigin = trim(\Safe\exec('git config --get remote.origin.url', result_code: $r3));
                $changes = \Safe\exec('git status -z', result_code: $r4);
                if ($r1 !== 0 || $r2 !== 0 || $r3 !== 0 || $r4 !== 0) {
                    throw new \Exception("Failed to get git data");
                }
                $commitOrigin = \Safe\preg_replace("#//.*@#", "//xxx@", $commitOrigin);
                $changeList = [];
                foreach (explode("\0", $changes) as $change) {
                    $parts = explode(" ", $change, 3);
                    if (count($parts) > 2) {
                        $changeList[] = $parts[2];
                    }
                }
                $info['git'] = [
                    'commit' => $commitHash,
                    'branch' => $commitBranch,
                    'origin' => $commitOrigin,
                ];
                if (count($changeList) > 0) {
                    $info["git"]['changes'] = $changeList;
                }
            } catch (\Exception $e) {
                // If we can't get git data, just skip it
            }
        }

        try {
            $mountinfos = explode("\n", \Safe\file_get_contents('/proc/self/mounts'));
            $mounts = [];
            $root = $_SERVER['DOCUMENT_ROOT'];
            foreach ($mountinfos as $mountinfo) {
                $parts = explode(' ', $mountinfo);
                if (count($parts) > 1 && str_starts_with($parts[1], $root)) {
                    $mounts[] = "./" . substr($parts[1], strlen($root));
                }
            }
            $info['media']['mounts'] = $mounts;
        } catch (\Exception $e) {
            // If we can't get mount data, just skip it
        }

        return $info;
    }

    /**
     * @return array<string, mixed>
     */
    private function get_system_info(): array
    {
        $info = [
            "server" => $_SERVER,
            "env" => $_ENV,
            "get" => $_GET,
            "cookie" => $_COOKIE,
            // These don't apply to "GET /system_info"
            // "post" => $_POST,
            // "files" => $_FILES,
            // "session" => $_SESSION,
            // "request" => $_REQUEST,
            "php" => [
                "extensions" => get_loaded_extensions(),
            ],
            "php_ini" => ini_get_all(),
        ];
        return $info;
    }
}
