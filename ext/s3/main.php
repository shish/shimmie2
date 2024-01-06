<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\INPUT;

require_once "config.php";

class S3 extends Extension
{
    public static array $synced = [];

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        global $config;

        $sb = $event->panel->create_new_block("S3 CDN");
        $sb->add_text_option(S3Config::ACCESS_KEY_ID, "Access Key ID: ");
        $sb->add_text_option(S3Config::ACCESS_KEY_SECRET, "<br>Access Key Secret: ");
        $sb->add_text_option(S3Config::ENDPOINT, "<br>Endpoint: ");
        $sb->add_text_option(S3Config::IMAGE_BUCKET, "<br>Image Bucket: ");
    }

    public function onCommand(CommandEvent $event)
    {
        if ($event->cmd == "help") {
            print "\ts3-sync <post id>\n";
            print "\t\tsync a post to s3\n\n";
            print "\ts3-rm <hash>\n";
            print "\t\tdelete a leftover file from s3\n\n";
        }
        if ($event->cmd == "s3-sync") {
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
        $this->sync_post($event->image, $event->tags);
    }

    public function onImageDeletion(ImageDeletionEvent $event)
    {
        $this->remove_file($event->image->hash);
    }

    public function onImageReplace(ImageReplaceEvent $event)
    {
        $this->remove_file($event->original->hash);
        $this->sync_post($event->replacement, $event->original->get_tag_array());
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

    // underlying s3 interaction functions
    private function sync_post(Image $image, ?array $new_tags = null, bool $overwrite = true): bool
    {
        global $config;

        // multiple events can trigger a sync,
        // let's only do one per request
        if(in_array($image->id, self::$synced)) {
            return false;
        }
        self::$synced[] = $image->id;

        $client = $this->get_client();
        if(is_null($client)) {
            return false;
        }
        $image_bucket = $config->get_string(S3Config::IMAGE_BUCKET);

        if(is_null($new_tags)) {
            $friendly = $image->parse_link_template('$id - $tags.$ext');
        } else {
            $_orig_tags = $image->get_tag_array();
            $image->tag_array = $new_tags;
            $friendly = $image->parse_link_template('$id - $tags.$ext');
            $image->tag_array = $_orig_tags;
        }

        $key = $this->hash_to_path($image->hash);
        if(!$overwrite && $client->doesObjectExist($image_bucket, $key)) {
            return false;
        }

        $client->putObject([
            'Bucket' => $image_bucket,
            'Key' => $key,
            'Body' => file_get_contents($image->get_image_filename()),
            'ACL' => 'public-read',
            'ContentType' => $image->get_mime(),
            'ContentDisposition' => "inline; filename=\"$friendly\"",
        ]);
        return true;
    }

    private function remove_file(string $hash)
    {
        global $config;
        $client = $this->get_client();
        if(is_null($client)) {
            return;
        }
        $image_bucket = $config->get_string(S3Config::IMAGE_BUCKET);
        $client->deleteObject([
            'Bucket' => $image_bucket,
            'Key' => $this->hash_to_path($hash),
        ]);
    }
}
