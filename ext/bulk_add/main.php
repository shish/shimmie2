<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

final class BulkAddEvent extends Event
{
    public Path $dir;
    /** @var UploadResult[] */
    public array $results;

    public function __construct(Path $dir)
    {
        parent::__construct();
        $this->dir = $dir;
        $this->results = [];
    }
}

/** @extends Extension<BulkAddTheme> */
final class BulkAdd extends Extension
{
    public const KEY = "bulk_add";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("bulk_add", method: "POST", permission: BulkAddPermission::BULK_ADD)) {
            $dir = $event->POST->req('dir');
            assert(!empty($dir), "Directory cannot be empty");
            Ctx::$event_bus->set_timeout(null);
            $bae = send_event(new BulkAddEvent(new Path("$dir/")));
            $this->theme->display_upload_results($bae->results);
        }
    }

    #[EventListener]
    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('bulk-add')
            ->addArgument('directory', InputArgument::REQUIRED)
            ->setDescription('Import a directory of images')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                if (!Ctx::$user->can(BulkAddPermission::BULK_ADD)) {
                    $output->writeln("You do not have permission to bulk add images");
                    return Command::FAILURE;
                }
                /** @var string $dir */
                $dir = $input->getArgument('directory');
                $bae = send_event(new BulkAddEvent(new Path("$dir/")));
                foreach ($bae->results as $r) {
                    if (is_a($r, UploadError::class)) {
                        $output->writeln($r->name." failed: ".$r->error);
                    } else {
                        $output->writeln($r->name." ok");
                    }
                }
                return Command::SUCCESS;
            });
    }

    #[EventListener]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_block();
    }

    #[EventListener]
    public function onBulkAdd(BulkAddEvent $event): void
    {
        if ($event->dir->is_dir() && $event->dir->is_readable()) {
            $event->results = send_event(new DirectoryUploadEvent($event->dir))->results;
        } else {
            $event->results = [new UploadError($event->dir->str(), "is not a readable directory")];
        }
    }
}
