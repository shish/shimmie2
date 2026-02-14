<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\INPUT;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;

require_once __DIR__ . "/S3.php";

final class S3 extends Extension
{
    public const KEY = "s3";
    public int $synced = 0;

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            $database->create_table("s3_sync_queue", "
                hash CHAR(32) NOT NULL PRIMARY KEY,
                time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                action CHAR(1) NOT NULL DEFAULT 'S'
            ");
            $this->set_version(1);
        }
    }

    #[EventListener]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        global $database;
        $count = $database->get_one("SELECT COUNT(*) FROM s3_sync_queue");
        $html = SHM_SIMPLE_FORM(
            make_link("admin/s3_process"),
            INPUT(["type" => 'number', "name" => 'count', 'value' => '10']),
            SHM_SUBMIT("Sync N/$count posts"),
        );
        Ctx::$page->add_block(new Block("Process S3 Queue", $html));
    }

    #[EventListener]
    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database;
        if ($event->action === "s3_process") {
            foreach ($database->get_all(
                "SELECT * FROM s3_sync_queue ORDER BY time ASC LIMIT :count",
                ["count" => isset($event->params['count']) ? int_escape($event->params["count"]) : 10]
            ) as $row) {
                if ($row['action'] === "S") {
                    $image = Image::by_hash($row['hash']);
                    if ($image) {
                        $this->sync_post($image);
                    }
                } elseif ($row['action'] === "D") {
                    $this->remove_file($row['hash']);
                }
            }
            $event->redirect = true;
        }
    }

    #[EventListener]
    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('s3:process')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of items to process')
            ->setDescription('Process the S3 queue')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $database;
                $count = $database->get_one("SELECT COUNT(*) FROM s3_sync_queue");
                $output->writeln("{$count} items in queue");
                foreach ($database->get_all(
                    "SELECT * FROM s3_sync_queue ORDER BY time ASC LIMIT :count",
                    ["count" => $input->getOption('count') ?? $count]
                ) as $row) {
                    if ($row['action'] === "S") {
                        $image = Image::by_hash($row['hash']);
                        if ($image) {
                            $output->writeln("SYN {$row['hash']} ($image->id)");
                            $this->sync_post($image);
                        }
                    } elseif ($row['action'] === "D") {
                        $output->writeln("DEL {$row['hash']}");
                        $this->remove_file($row['hash']);
                    } else {
                        $output->writeln("??? {$row['hash']} ({$row['action']})");
                    }
                }
                return Command::SUCCESS;
            });
        $event->app->register('s3:sync')
            ->addArgument('query', InputArgument::REQUIRED)
            ->setDescription('Search for some images, and sync them to s3')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $query = SearchTerm::explode($input->getArgument('query'));
                foreach (Search::find_images_iterable(terms: $query) as $image) {
                    $output->writeln("{$image->id}: {$image->hash}");
                    $this->sync_post($image);
                }
                return Command::SUCCESS;
            });
        $event->app->register('s3:rm')
            ->addArgument('hash', InputArgument::REQUIRED)
            ->setDescription('Delete a leftover file from S3')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $hash = $input->getArgument('hash');
                $output->writeln("Deleting file: '$hash'");
                $this->remove_file($hash);
                return Command::SUCCESS;
            });
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("s3/sync/{image_id}", method: "POST", permission: ImagePermission::DELETE_IMAGE)) {
            $id = $event->get_iarg('image_id');
            $this->sync_post(Image::by_id_ex($id));
            Log::info("s3", "Manual resync for >>$id", "File re-sync'ed");
            Ctx::$page->set_redirect(make_link("post/view/$id"));
        }
    }

    #[EventListener]
    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(ImagePermission::DELETE_IMAGE)) {
            $event->add_button("CDN Re-Sync", "s3/sync/{$event->image->id}");
        }
    }

    #[EventListener]
    public function onImageAddition(ImageAdditionEvent $event): void
    {
        // Tags aren't set at this point, let's wait for the TagSetEvent
        // $this->sync_post($event->image);
    }

    #[EventListener]
    public function onTagSet(TagSetEvent $event): void
    {
        $this->sync_post($event->image, $event->new_tags);
    }

    #[EventListener]
    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        $this->remove_file($event->image->hash);
    }

    #[EventListener]
    public function onImageReplace(ImageReplaceEvent $event): void
    {
        $this->remove_file($event->old_hash);
        $this->sync_post($event->image);
    }

    // utils
    private function get_client(): \S3Client\S3
    {
        $access_key_id = Ctx::$config->get(S3Config::ACCESS_KEY_ID);
        $access_key_secret = Ctx::$config->get(S3Config::ACCESS_KEY_SECRET);
        if (is_null($access_key_id) || is_null($access_key_secret)) {
            throw new ServerError("S3 credentials not set");
        }
        $endpoint = Ctx::$config->get(S3Config::ENDPOINT);
        $region = Ctx::$config->get(S3Config::REGION);

        return new \S3Client\S3(
            $access_key_id,
            $access_key_secret,
            $endpoint,
            $region
        );
    }

    private function hash_to_path(string $hash): string
    {
        $ha = substr($hash, 0, 2);
        $sh = substr($hash, 2, 2);
        return "$ha/$sh/$hash";
    }

    private function is_busy(): bool
    {
        $this->synced++;
        if (PHP_SAPI === "cli") {
            return false; // CLI can go on for as long as it wants
        }
        return $this->synced > Ctx::$config->get(UploadConfig::COUNT);
    }

    // underlying s3 interaction functions
    /**
     * @param list<tag-string>|null $new_tags
     */
    private function sync_post(Image $image, ?array $new_tags = null): void
    {
        if (defined("UNITTEST")) {
            return;
        }
        if ($this->is_busy()) {
            $this->enqueue($image->hash, "S");
        } else {
            if (is_null($new_tags)) {
                $friendly = $image->parse_link_template('$id - $tags.$ext');
            } else {
                $_orig_tags = $image->get_tag_array();
                $image->tag_array = $new_tags;
                $friendly = $image->parse_link_template('$id - $tags.$ext');
                $image->tag_array = $_orig_tags;
            }
            $client = $this->get_client();
            $bucket = Ctx::$config->get(S3Config::IMAGE_BUCKET);
            if ($bucket === null) {
                throw new ServerError("S3 bucket not set");
            }
            $client->putObject(
                $bucket,
                $this->hash_to_path($image->hash),
                $image->get_image_filename()->get_contents(),
                [
                    'x-amz-acl' => 'public-read',
                    'Content-Type' => (string)$image->get_mime(),
                    'Content-Disposition' => "inline; filename=\"$friendly\"",
                ]
            );
            $this->dequeue($image->hash);
        }
    }

    private function remove_file(string $hash): void
    {
        if (defined("UNITTEST")) {
            return;
        }
        if ($this->is_busy()) {
            $this->enqueue($hash, "D");
        } else {
            $client = $this->get_client();
            $bucket = Ctx::$config->get(S3Config::IMAGE_BUCKET);
            if ($bucket === null) {
                throw new ServerError("S3 bucket not set");
            }
            $client->deleteObject($bucket, $this->hash_to_path($hash));
            $this->dequeue($hash);
        }
    }

    private function enqueue(string $hash, string $action): void
    {
        global $database;
        $database->execute("DELETE FROM s3_sync_queue WHERE hash = :hash", ["hash" => $hash]);
        $database->execute("
            INSERT INTO s3_sync_queue (hash, action)
            VALUES (:hash, :action)
        ", ["hash" => $hash, "action" => $action]);
    }

    private function dequeue(string $hash): void
    {
        global $database;
        $database->execute("DELETE FROM s3_sync_queue WHERE hash = :hash", ["hash" => $hash]);
    }
}
