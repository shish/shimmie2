<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ET extends Extension
{
    public const KEY = "et";
    /** @var ETTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("system_info", permission: ETPermission::VIEW_SYSINFO)) {
            $this->theme->display_info_page(
                $this->to_yaml($this->get_site_info()),
                $this->to_yaml($this->get_system_info()),
            );
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(ETPermission::VIEW_SYSINFO)) {
                $event->add_nav_link(make_link('system_info'), "System Info", order: 10);
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
                print($this->to_yaml($this->get_site_info()));
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
                'schema'  => $config->get("db_version"),
                'php'     => phpversion(),
                'db'      => $database->get_driver_id()->value . " " . $database->get_version(),
                'os'      => php_uname(),
                'server'  =>  $_SERVER["SERVER_SOFTWARE"] ?? 'unknown',
            ],
            "extensions" => [
                "core" => $core_exts,
                "extra" => $extra_exts,
                "handled_mimes" => array_map(fn ($mime) => (string)$mime, DataHandlerExtension::get_all_supported_mimes()),
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
                "quality" => $config->get(ThumbnailConfig::QUALITY),
                "width" => $config->get(ThumbnailConfig::WIDTH),
                "height" => $config->get(ThumbnailConfig::HEIGHT),
                "scaling" => $config->get(ThumbnailConfig::SCALING),
                "mime" => $config->get(ThumbnailConfig::MIME),
            ],
        ];

        if (file_exists(".git")) {
            try {
                $commitHash = trim(\Safe\exec('git log --pretty="%h" -n1 HEAD', result_code: $r1));
                $commitBranch = trim(\Safe\exec('git rev-parse --abbrev-ref HEAD', result_code: $r2));
                $commitOrigin = trim(\Safe\exec('git config --get remote.origin.url', result_code: $r3));
                if ($r1 !== 0 || $r2 !== 0 || $r3 !== 0) {
                    throw new \Exception("Failed to get git data");
                }
                $commitOrigin = \Safe\preg_replace("#//.*@#", "//xxx@", $commitOrigin);
                $info['versions']['shimmie'] .= $commitHash;
                $info['versions']['origin'] = "$commitOrigin ($commitBranch)";
                $info['git'] = [
                    'commit' => $commitHash,
                    'branch' => $commitBranch,
                    'origin' => $commitOrigin,
                ];
            } catch (\Exception $e) {
                // If we can't get git data, just skip it
            }
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

    /**
     * @param array<string, mixed> $info
     */
    private function to_yaml(array $info): string
    {
        $data = "";
        foreach ($info as $title => $section) {
            if (!empty($section)) {
                $data .= "$title:\n";
                foreach ($section as $k => $v) {
                    try {
                        $data .= "  $k: " . \Safe\json_encode($v, JSON_UNESCAPED_SLASHES) . "\n";
                    } catch (\Exception $e) {
                        $data .= "  $k: \"(encode error)\"\n";
                    }
                }
                $data .= "\n";
            }
        }
        return $data;
    }
}
