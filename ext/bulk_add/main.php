<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

class BulkAddEvent extends Event
{
    public string $dir;
    /** @var UploadResult[] */
    public array $results;

    public function __construct(string $dir)
    {
        parent::__construct();
        $this->dir = $dir;
        $this->results = [];
    }
}

class BulkAdd extends Extension
{
    /** @var BulkAddTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;
        if ($event->page_matches("bulk_add", method: "POST", permission: Permissions::BULK_ADD)) {
            $dir = $event->req_POST('dir');
            shm_set_timeout(null);
            $bae = send_event(new BulkAddEvent($dir));
            $this->theme->display_upload_results($page, $bae->results);
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('bulk-add')
            ->addArgument('directory', InputArgument::REQUIRED)
            ->setDescription('Import a directory of images')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $dir = $input->getArgument('directory');
                $bae = send_event(new BulkAddEvent($dir));
                foreach ($bae->results as $r) {
                    if(is_a($r, UploadError::class)) {
                        $output->writeln($r->name." failed: ".$r->error);
                    } else {
                        $output->writeln($r->name." ok");
                    }
                }
                return Command::SUCCESS;
            });
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_block();
    }

    public function onBulkAdd(BulkAddEvent $event): void
    {
        if (is_dir($event->dir) && is_readable($event->dir)) {
            $event->results = add_dir($event->dir);
        } else {
            $event->results = [new UploadError($event->dir, "is not a readable directory")];
        }
    }
}
