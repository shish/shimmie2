<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\{InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

final class DevTools extends Extension
{
    public const KEY = "devtools";
    /** @var DevToolsTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('dev:list-events')
            ->setDescription('List all events')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                foreach (get_declared_classes() as $class) {
                    if (is_subclass_of($class, Event::class)) {
                        $output->writeln($class);
                        // show constructor parameters for $class
                        $reflection = new \ReflectionClass($class);
                        $constructor = $reflection->getConstructor();
                        if ($constructor) {
                            $params = $constructor->getParameters();
                            foreach ($params as $param) {
                                $type = $param->getType();
                                if ($type) {
                                    $output->writeln("  - " . $param->getName() . ": " . $type->getName());
                                } else {
                                    $output->writeln("  - " . $param->getName() . ": ?");
                                }
                            }
                        }
                    }
                }
                return Command::SUCCESS;
            });
        $event->app->register('dev:create-ext')
            ->addArgument('name', InputArgument::REQUIRED)
            ->setDescription('Create a skeleton for a new extension')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $name = $input->getArgument('name');
                if (file_exists("ext/$name")) {
                    $output->writeln("Extension $name already exists");
                    return Command::FAILURE;
                }
                return Command::SUCCESS;
            });
        $event->app->register('dev:create-theme')
            ->addArgument('name', InputArgument::REQUIRED)
            ->setDescription('Create a skeleton for a new theme')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $name = $input->getArgument('name');
                if (file_exists("ext/$name")) {
                    $output->writeln("Extension $name already exists");
                    return Command::FAILURE;
                }
                return Command::SUCCESS;
            });
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (Ctx::$user->can(DevToolsPermission::MANAGE_DEVTOOLS)) {
                $event->add_nav_link(make_link('devtools'), "DevTools");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(DevToolsPermission::MANAGE_DEVTOOLS)) {
            $event->add_link("DevTools", make_link("devtools"));
        }
    }
}
