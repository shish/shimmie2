<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ExtensionAuthor
{
    public function __construct(
        public string $name,
        public ?string $email
    ) {
    }
}

final class ExtManager extends Extension
{
    public const KEY = "ext_manager";
    /** @var ExtManagerTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("ext_manager/set", method: "POST", permission: ExtManagerPermission::MANAGE_EXTENSION_LIST)) {
            if (is_writable("data/config")) {
                $this->set_things($event->POST);
                Log::warning("ext_manager", "Active extensions changed", "Active extensions changed");
                Ctx::$page->set_redirect(make_link("ext_manager"));
            } else {
                throw new ServerError("The config file (data/config/extensions.conf.php) isn't writable by the web server :(");
            }
        } elseif ($event->page_matches("ext_manager", method: "GET")) {
            $is_admin = Ctx::$user->can(ExtManagerPermission::MANAGE_EXTENSION_LIST);
            $this->theme->display_table($this->get_extensions($is_admin), $is_admin);
        }

        if ($event->page_matches("ext_doc/{ext}")) {
            $ext = $event->get_arg('ext');
            $info = ExtensionInfo::get_all()[$ext];
            $this->theme->display_doc($info);
        } elseif ($event->page_matches("ext_doc")) {
            $this->theme->display_table($this->get_extensions(false), false);
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('disable-all-ext')
            ->setDescription('Disable all extensions')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $this->write_config([]);
                return Command::SUCCESS;
            });
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(ExtManagerPermission::MANAGE_EXTENSION_LIST)) {
                $event->add_nav_link(make_link('ext_manager'), "Extension Manager");
            } else {
                $event->add_nav_link(make_link('ext_doc'), "Board Help");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(ExtManagerPermission::MANAGE_EXTENSION_LIST)) {
            $event->add_link("Extension Manager", make_link("ext_manager"));
        }
    }

    /**
     * @return ExtensionInfo[]
     */
    private function get_extensions(bool $all): array
    {
        $extensions = array_values(ExtensionInfo::get_all());
        if (!$all) {
            $extensions = array_filter($extensions, fn ($x) => $x::is_enabled());
        }
        usort($extensions, function ($a, $b) {
            if ($a->category->name !== $b->category->name) {
                return $a->category->name <=> $b->category->name;
            }
            if ($a->beta !== $b->beta) {
                return $a->beta <=> $b->beta;
            }
            return strcmp($a->name, $b->name);
        });
        return $extensions;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function set_things(array $settings): void
    {
        $extras = [];

        foreach (ExtensionInfo::get_all() as $key => $info) {
            if ($info->core) {
                continue;  // core extensions are always enabled
            }
            if (isset($settings["ext_$key"]) && $settings["ext_$key"] === "on") {
                $extras[] = $key;
            }
        }

        $this->write_config($extras);
    }

    /**
     * @param string[] $extras
     */
    private function write_config(array $extras): void
    {
        $contents = implode(", ", array_map(fn ($x) => "'$x'", $extras));
        file_put_contents(
            "data/config/extensions.conf.php",
            '<' . '?php' . "\n" .
            'define("EXTRA_EXTS", [' . $contents . ']);' . "\n"
        );
    }
}
