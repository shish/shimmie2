<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

require_once "events.php";
require_once "panel.php";

class Setup extends Extension
{
    /** @var SetupTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string(SetupConfig::TITLE, "Shimmie");
        $config->set_default_string(SetupConfig::FRONT_PAGE, "post/list");
        $config->set_default_string(SetupConfig::MAIN_PAGE, "post/list");
        $config->set_default_string(SetupConfig::THEME, "default");
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page, $user;

        if ($event->page_starts_with("nicedebug")) {
            $page->set_mode(PageMode::DATA);
            $page->set_data(\Safe\json_encode([
                "args" => $event->args,
            ]));
        }

        if ($event->page_matches("nicetest")) {
            $page->set_mode(PageMode::DATA);
            $page->set_data("ok");
        }

        if ($event->page_matches("setup/advanced", method: "GET", permission: Permissions::CHANGE_SETTING)) {
            $this->theme->display_advanced($page, $config->values);
        } elseif ($event->page_matches("setup", method: "GET", permission: Permissions::CHANGE_SETTING)) {
            $panel = new SetupPanel($config);
            send_event(new SetupBuildingEvent($panel));
            $this->theme->display_page($page, $panel);
        } elseif ($event->page_matches("setup/save", method: "POST", permission: Permissions::CHANGE_SETTING)) {
            send_event(new ConfigSaveEvent($config, ConfigSaveEvent::postToSettings($event->POST)));
            $page->flash("Config saved");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("setup"));
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $event->panel->add_config_group(new SetupConfig());
    }

    public function onConfigSave(ConfigSaveEvent $event): void
    {
        $config = $event->config;
        foreach ($event->values as $key => $value) {
            match(true) {
                is_null($value) => $config->delete($key),
                is_string($value) => $config->set_string($key, $value),
                is_int($value) => $config->set_int($key, $value),
                is_bool($value) => $config->set_bool($key, $value),
                is_array($value) => $config->set_array($key, $value),
            };
        }
        log_warning("setup", "Configuration updated");
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('config:get')
            ->addArgument('key', InputArgument::REQUIRED)
            ->setDescription('Get a config value')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $config;
                $output->writeln($config->get_string($input->getArgument('key')));
                return Command::SUCCESS;
            });
        $event->app->register('config:set')
            ->addArgument('key', InputArgument::REQUIRED)
            ->addArgument('value', InputArgument::REQUIRED)
            ->setDescription('Set a config value')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $cache, $config;
                $config->set_string($input->getArgument('key'), $input->getArgument('value'));
                $cache->delete("config");
                return Command::SUCCESS;
            });
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "system") {
            if ($user->can(Permissions::CHANGE_SETTING)) {
                $event->add_nav_link("setup", new Link('setup'), "Board Config", null, 0);
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::CHANGE_SETTING)) {
            $event->add_link("Board Config", make_link("setup"));
        }
    }

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        global $config;
        $event->replace('$base', $config->get_string('base_href'));
        $event->replace('$title', $config->get_string(SetupConfig::TITLE));
    }
}
