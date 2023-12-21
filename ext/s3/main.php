<?php

declare(strict_types=1);

namespace Shimmie2;

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
                print("{$image->id}: {$image->hash}\n");
                ob_flush();
                $this->sync_post($image);
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

    public function onImageAddition(ImageAdditionEvent $event)
    {
        $this->sync_post($event->image);
    }

    public function onTagSet(TagSetEvent $event)
    {
        // pretend that tags were set already so that sync works
        $orig_tags = $event->image->tag_array;
        $event->image->tag_array = $event->tags;
        $this->sync_post($event->image);
        $event->image->tag_array = $orig_tags;
    }

    public function onImageDeletion(ImageDeletionEvent $event)
    {
        $this->remove_file($event->image->hash);
    }

    public function onImageReplace(ImageReplaceEvent $event)
    {
        $existing = Image::by_id($event->id);
        $this->remove_file($existing->hash);
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

    // underlying s3 interaction functions
    private function sync_post(Image $image)
    {
        global $config;

        // multiple events can trigger a sync,
        // let's only do one per request
        if(in_array($image->id, self::$synced)) {
            return;
        }
        self::$synced[] = $image->id;

        $client = $this->get_client();
        if(is_null($client)) {
            return;
        }
        $image_bucket = $config->get_string(S3Config::IMAGE_BUCKET);
        $friendly = $image->parse_link_template('$id - $tags.$ext');
        $client->putObject([
            'Bucket' => $image_bucket,
            'Key' => $this->hash_to_path($image->hash),
            'Body' => file_get_contents($image->get_image_filename()),
            'ACL' => 'public-read',
            'ContentType' => $image->get_mime(),
            'ContentDisposition' => "inline; filename=\"$friendly\"",
        ]);
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
