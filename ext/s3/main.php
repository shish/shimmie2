<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument,InputOption};
use Symfony\Component\Console\Output\OutputInterface;

use function MicroHTML\INPUT;

require_once "config.php";

class S3 extends Extension
{
    public int $synced = 0;

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        global $config;

        $sb = $event->panel->create_new_block("S3 CDN");
        $sb->add_text_option(S3Config::ACCESS_KEY_ID, "Access Key ID: ");
        $sb->add_text_option(S3Config::ACCESS_KEY_SECRET, "<br>Access Key Secret: ");
        $sb->add_text_option(S3Config::ENDPOINT, "<br>Endpoint: ");
        $sb->add_text_option(S3Config::IMAGE_BUCKET, "<br>Image Bucket: ");
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version("ext_s3_version") < 1) {
            $database->create_table("s3_sync_queue", "
                hash CHAR(32) NOT NULL PRIMARY KEY,
                time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                action CHAR(1) NOT NULL DEFAULT 'S'
            ");
            $this->set_version("ext_s3_version", 1);
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        global $database, $page;
        $count = $database->get_one("SELECT COUNT(*) FROM s3_sync_queue");
        $html = SHM_SIMPLE_FORM(
            "admin/s3_process",
            INPUT(["type" => 'number', "name" => 'count', 'value' => '10']),
            SHM_SUBMIT("Sync N/$count posts"),
        );
        $page->add_block(new Block("Process S3 Queue", $html));
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database;
        if($event->action == "s3_process") {
            foreach($database->get_all(
                "SELECT * FROM s3_sync_queue ORDER BY time ASC LIMIT :count",
                ["count" => isset($event->params['count']) ? int_escape($event->params["count"]) : 10]
            ) as $row) {
                if($row['action'] == "S") {
                    $image = Image::by_hash($row['hash']);
                    $this->sync_post($image);
                } elseif($row['action'] == "D") {
                    $this->remove_file($row['hash']);
                }
            }
            $event->redirect = true;
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('s3:process')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of items to process')
            ->setDescription('Process the S3 queue')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $database;
                $count = $database->get_one("SELECT COUNT(*) FROM s3_sync_queue");
                $output->writeln("{$count} items in queue");
                foreach($database->get_all(
                    "SELECT * FROM s3_sync_queue ORDER BY time ASC LIMIT :count",
                    ["count" => $input->getOption('count') ?? $count]
                ) as $row) {
                    if($row['action'] == "S") {
                        $image = Image::by_hash($row['hash']);
                        $output->writeln("SYN {$row['hash']} ($image->id)");
                        $this->sync_post($image);
                    } elseif($row['action'] == "D") {
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
                $query = Tag::explode($input->getArgument('query'));
                foreach(Search::find_images_iterable(tags: $query) as $image) {
                    if($this->sync_post($image)) {
                        print("{$image->id}: {$image->hash}\n");
                    } else {
                        print("{$image->id}: {$image->hash} (skipped)\n");
                    }
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

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page, $user;
        if ($event->page_matches("s3/sync/{image_id}", method: "POST", permission: Permissions::DELETE_IMAGE)) {
            $id = $event->get_iarg('image_id');
            $this->sync_post(Image::by_id_ex($id));
            log_info("s3", "Manual resync for >>$id", "File re-sync'ed");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$id"));
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::DELETE_IMAGE)) {
            $event->add_button("CDN Re-Sync", "s3/sync/{$event->image->id}");
        }
    }

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        // Tags aren't set at this point, let's wait for the TagSetEvent
        // $this->sync_post($event->image);
    }

    public function onTagSet(TagSetEvent $event): void
    {
        $this->sync_post($event->image, $event->new_tags);
    }

    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        $this->remove_file($event->image->hash);
    }

    public function onImageReplace(ImageReplaceEvent $event): void
    {
        $this->remove_file($event->old_hash);
        $this->sync_post($event->image);
    }

    // utils
    private function get_client(): ?\Aws\S3\S3Client
    {
        global $config;
        $access_key_id = $config->get_string(S3Config::ACCESS_KEY_ID);
        $access_key_secret = $config->get_string(S3Config::ACCESS_KEY_SECRET);
        if(is_null($access_key_id) || is_null($access_key_secret)) {
            return null;
        }
        $endpoint = $config->get_string(S3Config::ENDPOINT);
        $credentials = new \Aws\Credentials\Credentials($access_key_id, $access_key_secret);
        return new \Aws\S3\S3Client([
            'region' => 'auto',
            'endpoint' => $endpoint,
            'version' => 'latest',
            'credentials' => $credentials,
        ]);
    }

    private function hash_to_path(string $hash): string
    {
        $ha = substr($hash, 0, 2);
        $sh = substr($hash, 2, 2);
        return "$ha/$sh/$hash";
    }

    private function is_busy(): bool
    {
        global $config;
        $this->synced++;
        if(PHP_SAPI == "cli") {
            return false; // CLI can go on for as long as it wants
        }
        return $this->synced > $config->get_int(UploadConfig::COUNT);
    }

    // underlying s3 interaction functions
    /**
     * @param string[]|null $new_tags
     */
    private function sync_post(Image $image, ?array $new_tags = null, bool $overwrite = true): bool
    {
        global $config;

        $client = $this->get_client();
        if(is_null($client)) {
            return false;
        }
        $image_bucket = $config->get_string(S3Config::IMAGE_BUCKET);

        $key = $this->hash_to_path($image->hash);
        if(!$overwrite && $client->doesObjectExist($image_bucket, $key)) {
            return false;
        }

        if($this->is_busy()) {
            $this->enqueue($image->hash, "S");
        } else {
            if(is_null($new_tags)) {
                $friendly = $image->parse_link_template('$id - $tags.$ext');
            } else {
                $_orig_tags = $image->get_tag_array();
                $image->tag_array = $new_tags;
                $friendly = $image->parse_link_template('$id - $tags.$ext');
                $image->tag_array = $_orig_tags;
            }
            $client->putObject([
                'Bucket' => $image_bucket,
                'Key' => $key,
                'Body' => \Safe\file_get_contents($image->get_image_filename()),
                'ACL' => 'public-read',
                'ContentType' => $image->get_mime(),
                'ContentDisposition' => "inline; filename=\"$friendly\"",
            ]);
            $this->dequeue($image->hash);
        }
        return true;
    }

    private function remove_file(string $hash): void
    {
        global $config;
        $client = $this->get_client();
        if(is_null($client)) {
            return;
        }
        if($this->is_busy()) {
            $this->enqueue($hash, "D");
        } else {
            $client->deleteObject([
                'Bucket' => $config->get_string(S3Config::IMAGE_BUCKET),
                'Key' => $this->hash_to_path($hash),
            ]);
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
