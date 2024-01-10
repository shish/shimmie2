<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\INPUT;

require_once "config.php";

class S3 extends Extension
{
    public int $synced = 0;

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        global $config;

        $sb = $event->panel->create_new_block("S3 CDN");
        $sb->add_text_option(S3Config::ACCESS_KEY_ID, "Access Key ID: ");
        $sb->add_text_option(S3Config::ACCESS_KEY_SECRET, "<br>Access Key Secret: ");
        $sb->add_text_option(S3Config::ENDPOINT, "<br>Endpoint: ");
        $sb->add_text_option(S3Config::IMAGE_BUCKET, "<br>Image Bucket: ");
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
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

    public function onCommand(CommandEvent $event)
    {
        global $database;
        if ($event->cmd == "help") {
            print "\ts3-sync <post id>\n";
            print "\t\tsync a post to s3\n\n";
            print "\ts3-rm <hash>\n";
            print "\t\tdelete a leftover file from s3\n\n";
        }
        if ($event->cmd == "s3-sync") {
            if(count($event->args) == 0) {
                $count = $database->get_one("SELECT COUNT(*) FROM s3_sync_queue");
                print("{$count} items in queue\n");
                foreach($database->get_all("SELECT * FROM s3_sync_queue ORDER BY time ASC") as $row) {
                    if($row['action'] == "S") {
                        $image = Image::by_hash($row['hash']);
                        print("SYN {$row['hash']} ($image->id)\n");
                        $this->sync_post($image);
                    } elseif($row['action'] == "D") {
                        print("DEL {$row['hash']}\n");
                        $this->remove_file($row['hash']);
                    } else {
                        print("??? {$row['hash']} ({$row['action']})\n");
                    }
                }
            } else {
                if (preg_match('/^(\d+)-(\d+)$/', $event->args[0], $matches)) {
                    $start = (int)$matches[1];
                    $end = (int)$matches[2];
                } else {
                    $start = (int)$event->args[0];
                    $end = $start;
                }
                foreach(Search::find_images_iterable(tags: ["order=id", "id>=$start", "id<=$end"]) as $image) {
                    if($this->sync_post($image)) {
                        print("{$image->id}: {$image->hash}\n");
                    } else {
                        print("{$image->id}: {$image->hash} (skipped)\n");
                    }
                    ob_flush();
                }
            }
        }
        if ($event->cmd == "s3-rm") {
            foreach($event->args as $hash) {
                print("{$hash}\n");
                ob_flush();
                $this->remove_file($hash);
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $config, $page, $user;
        if ($event->page_matches("s3/sync")) {
            if ($user->check_auth_token()) {
                if ($user->can(Permissions::DELETE_IMAGE) && isset($_POST['image_id'])) {
                    $id = int_escape($_POST['image_id']);
                    if ($id > 0) {
                        $this->sync_post(Image::by_id($id));
                        log_info("s3", "Manual resync for >>$id", "File re-sync'ed");
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(make_link("post/view/$id"));
                    }
                }
            }
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::DELETE_IMAGE)) {
            $event->add_part(SHM_SIMPLE_FORM(
                "s3/sync",
                INPUT(["type" => 'hidden', "name" => 'image_id', "value" => $event->image->id]),
                INPUT(["type" => 'submit', "value" => 'CDN Re-Sync']),
            ));
        }
    }

    public function onImageAddition(ImageAdditionEvent $event)
    {
        // Tags aren't set at this point, let's wait for the TagSetEvent
        // $this->sync_post($event->image);
    }

    public function onTagSet(TagSetEvent $event)
    {
        $this->sync_post($event->image, $event->new_tags);
    }

    public function onImageDeletion(ImageDeletionEvent $event)
    {
        $this->remove_file($event->image->hash);
    }

    public function onImageReplace(ImageReplaceEvent $event)
    {
        $this->remove_file($event->old_hash);
        $this->sync_post($event->image);
    }

    // utils
    private function get_client()
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

    private function hash_to_path(string $hash)
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
                'Body' => file_get_contents($image->get_image_filename()),
                'ACL' => 'public-read',
                'ContentType' => $image->get_mime(),
                'ContentDisposition' => "inline; filename=\"$friendly\"",
            ]);
            $this->dequeue($image->hash);
        }
        return true;
    }

    private function remove_file(string $hash)
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

    private function enqueue(string $hash, string $action)
    {
        global $database;
        $database->execute("DELETE FROM s3_sync_queue WHERE hash = :hash", ["hash" => $hash]);
        $database->execute("
            INSERT INTO s3_sync_queue (hash, action)
            VALUES (:hash, :action)
        ", ["hash" => $hash, "action" => $action]);
    }

    private function dequeue(string $hash)
    {
        global $database;
        $database->execute("DELETE FROM s3_sync_queue WHERE hash = :hash", ["hash" => $hash]);
    }
}
