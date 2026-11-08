<?php

declare(strict_types=1);

namespace Shimmie2;

use Jfcherng\Diff\DiffHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;

/*
 * Sent when the setup screen's 'Save Settings' button has been activated
 */
final class ConfigSaveEvent extends Event
{
    /**
     * @param array<string, null|string|int|boolean|array<string>> $values
     */
    public function __construct(
        public Config $config,
        public array $values
    ) {
        parent::__construct();
    }
}

/** @extends Extension<SetupTheme> */
final class Setup extends Extension
{
    public const KEY = "setup";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $config = Ctx::$config;
        $page = Ctx::$page;

        if ($event->page_starts_with("nicedebug")) {
            $page->set_data(MimeType::JSON, \Safe\json_encode([
                "args" => explode("/", $event->path),
                "theme" => get_theme(),
                "nice_urls" => Url::are_niceurls_enabled(),
                "base" => (string)Url::base(),
                "absolute_base" => (string)Url::base()->asAbsolute(),
                "base_link" => (string)make_link(""),
                "search_example" => (string)search_link(["AC/DC"]),
            ]));
        }

        if ($event->page_matches("nicetest")) {
            $page->set_data(MimeType::TEXT, "ok");
        }

        if ($event->page_matches("setup", method: "GET", permission: SetupPermission::CHANGE_SETTING)) {
            $blocks = [];
            foreach (ConfigGroup::get_subclasses() as $class) {
                $group = $class->newInstance();
                if ($group::is_enabled()) {
                    $block = $this->theme->config_group_to_block($config, $group);
                    if ($block) {
                        $blocks[] = $block;
                    }
                }
            }
            $this->theme->display_page($blocks);
        } elseif ($event->page_matches("setup/save", method: "POST", permission: SetupPermission::CHANGE_SETTING)) {
            send_event(new ConfigSaveEvent($config, ConfigGroup::fromPost($event->POST)));
            $page->flash("Config saved");
            $page->set_redirect(Url::referer_or(make_link("setup")));
        }
    }

    #[EventListener]
    public function onConfigSave(ConfigSaveEvent $event): void
    {
        $config = $event->config;
        $n = 0;
        $all_metas = ConfigGroup::get_all_metas();
        foreach ($event->values as $key => $value) {
            $old_value = $config->get($key);
            match(true) {
                is_null($value) => $config->delete($key),
                default => $config->set($key, $value),
            };
            if ($old_value !== $value) {
                $is_sensitive = isset($all_metas[$key]) && $all_metas[$key]->sensitive;
                $old_str = var_export($old_value, true);
                $new_str = var_export($value, true);

                if ($is_sensitive) {
                    Log::info("setup", "Sensitive config '$key' changed");
                } elseif (str_contains($old_str, "\n") || str_contains($new_str, "\n")) {
                    $diff = DiffHelper::calculate($old_str, $new_str, "Unified");
                    Log::info("setup", "Config '$key' changed (diff):\n" . $diff);
                } else {
                    Log::info("setup", "Config '$key' changed: " . $old_str . " / " . $new_str);
                }
                $n++;
            }
        }
        if ($n > 0) {
            Log::warning("setup", "Config saved ($n changes)");
        }
    }

    #[EventListener]
    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('config:defaults')
            ->setDescription('Show defaults')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                foreach (ConfigGroup::get_all_metas() as $key => $meta) {
                    $output->writeln("$key: " . \Safe\json_encode($meta->default, JSON_UNESCAPED_SLASHES));
                }
                return Command::SUCCESS;
            });
        $event->app->register('config:list')
            ->setDescription('Show all config values')
            ->addOption('changed', null, InputOption::VALUE_NONE, 'Show only values that differ from defaults')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $showChanged = $input->getOption('changed');
                foreach (ConfigGroup::get_all_metas() as $key => $meta) {
                    $value = Ctx::$config->get($key);
                    if ($showChanged && $value === $meta->default) {
                        continue;
                    }
                    $output->writeln("$key: " . \Safe\json_encode($value, JSON_UNESCAPED_SLASHES));
                }
                return Command::SUCCESS;
            });
        $event->app->register('config:get')
            ->addArgument('key', InputArgument::REQUIRED)
            ->setDescription('Get a config value')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $output->writeln(\Safe\json_encode(Ctx::$config->get($input->getArgument('key')), JSON_UNESCAPED_SLASHES));
                return Command::SUCCESS;
            });
        $event->app->register('config:set')
            ->addArgument('key', InputArgument::REQUIRED)
            ->addArgument('value', InputArgument::REQUIRED)
            ->setDescription('Set a config value')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                Ctx::$config->set($input->getArgument('key'), $input->getArgument('value'));
                Ctx::$cache->delete("config");
                return Command::SUCCESS;
            });
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(SetupPermission::CHANGE_SETTING)) {
                $event->add_nav_link(make_link('setup'), "Board Config", order: 0);
            }
        }
    }

    #[EventListener]
    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(SetupPermission::CHANGE_SETTING)) {
            $event->add_link("Board Config", make_link("setup"));
        }
    }

    #[EventListener]
    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        $event->replace('$title', Ctx::$config->get(SetupConfig::TITLE));
    }
}
