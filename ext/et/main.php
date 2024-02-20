<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

class ET extends Extension
{
    /** @var ETTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $user;
        if ($event->page_matches("system_info", permission: Permissions::VIEW_SYSINFO)) {
            $this->theme->display_info_page($this->to_yaml($this->get_info()));
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(Permissions::VIEW_SYSINFO)) {
                $event->add_nav_link("system_info", new Link('system_info'), "System Info", null, 10);
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::VIEW_SYSINFO)) {
            $event->add_link("System Info", make_link("system_info"), 99);
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('info')
            ->setDescription('List a bunch of info')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                print($this->to_yaml($this->get_info()));
                return Command::SUCCESS;
            });
    }

    /**
     * Collect the information and return it in a keyed array.
     *
     * @return array<string, mixed>
     */
    private function get_info(): array
    {
        global $config, $database;

        $core_exts = ExtensionInfo::get_core_extensions();
        $extra_exts = [];
        foreach (ExtensionInfo::get_all() as $info) {
            if ($info->is_enabled() && !in_array($info->key, $core_exts)) {
                $extra_exts[] = $info->key;
            }
        }

        $ver = VERSION;
        if(defined("BUILD_TIME")) {
            $ver .= "-" . substr(str_replace("-", "", constant("BUILD_TIME")), 0, 8);
        }
        if(defined("BUILD_HASH")) {
            $ver .= "-" . substr(constant("BUILD_HASH"), 0, 7);
        }
        if(file_exists(".git")) {
            $ver .= "+";
        }

        $disk_total = \Safe\disk_total_space("./");
        $disk_free = \Safe\disk_free_space("./");
        $info = [
            "about" => [
                'title' => $config->get_string(SetupConfig::TITLE),
                'theme' => $config->get_string(SetupConfig::THEME),
                'url'   => make_http(make_link("/")),
            ],
            "versions" => [
                'shimmie' => $ver,
                'schema'  => $config->get_int("db_version"),
                'php'     => phpversion(),
                'db'      => $database->get_driver_id()->value . " " . $database->get_version(),
                'os'      => php_uname(),
                'server'  =>  $_SERVER["SERVER_SOFTWARE"] ?? 'unknown',
            ],
            "extensions" => [
                "core" => $core_exts,
                "extra" => $extra_exts,
                "handled_mimes" => DataHandlerExtension::get_all_supported_mimes(),
            ],
            "stats" => [
                'images'   => (int)$database->get_one("SELECT COUNT(*) FROM images"),
                'comments' => (int)$database->get_one("SELECT COUNT(*) FROM comments"),
                'users'    => (int)$database->get_one("SELECT COUNT(*) FROM users"),
            ],
            "media" => [
                "memory_limit" => to_shorthand_int($config->get_int(MediaConfig::MEM_LIMIT)),
                "disk_use" => to_shorthand_int($disk_total - $disk_free),
                "disk_total" => to_shorthand_int($disk_total),
            ],
            "thumbnails" => [
                "engine" => $config->get_string(ImageConfig::THUMB_ENGINE),
                "quality" => $config->get_int(ImageConfig::THUMB_QUALITY),
                "width" => $config->get_int(ImageConfig::THUMB_WIDTH),
                "height" => $config->get_int(ImageConfig::THUMB_HEIGHT),
                "scaling" => $config->get_int(ImageConfig::THUMB_SCALING),
                "mime" => $config->get_string(ImageConfig::THUMB_MIME),
            ],
        ];

        if (file_exists(".git")) {
            try {
                $commitHash = trim(\Safe\exec('git log --pretty="%h" -n1 HEAD'));
                $commitBranch = trim(\Safe\exec('git rev-parse --abbrev-ref HEAD'));
                $commitOrigin = trim(\Safe\exec('git config --get remote.origin.url'));
                $commitOrigin = preg_replace("#//.*@#", "//xxx@", $commitOrigin);
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
     * @param array<string, mixed> $info
     */
    private function to_yaml(array $info): string
    {
        $data = "";
        foreach ($info as $title => $section) {
            $data .= "$title:\n";
            foreach ($section as $k => $v) {
                $data .= "  $k: " . \Safe\json_encode($v, JSON_UNESCAPED_SLASHES) . "\n";
            }
            $data .= "\n";
        }
        return $data;
    }
}
